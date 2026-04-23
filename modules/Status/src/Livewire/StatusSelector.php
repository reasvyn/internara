<?php

declare(strict_types=1);

namespace Modules\Status\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Status\Enums\Status;
use Modules\Status\Policies\StatusChangePolicy;
use Modules\Status\Services\StatusTransitionService;
use Modules\User\Models\User;

/**
 * Livewire component for selecting and applying account status changes.
 *
 * Displays available status transitions as radio buttons with descriptions,
 * color badges, and transition rules. Includes admin notes field for logging
 * the reason for the status change.
 */
class StatusSelector extends Component
{
    public User $user;

    public ?string $selectedStatus = null;

    public ?string $adminNotes = '';

    public bool $showTransitionForm = false;

    protected $rules = [
        'selectedStatus' => 'required|string',
        'adminNotes' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'selectedStatus.required' => 'Silakan pilih status akun yang akan diterapkan',
        'adminNotes.max' => 'Catatan tidak boleh melebihi 500 karakter',
    ];

    /**
     * Mount the component with the user to manage.
     */
    public function mount(User $user): void
    {
        $this->user = $user;
        $this->selectedStatus = $user->getStatus()?->value;

        // Check authorization
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->can('transition', $this->user), 403);
    }

    /**
     * Get available status transitions for the user.
     */
    public function getAvailableTransitions(): array
    {
        $currentStatus = $this->user->getStatus();
        $availableStatuses = [];

        foreach ($currentStatus->validTransitions() as $nextStatus) {
            $availableStatuses[] = [
                'status' => $nextStatus,
                'label' => $nextStatus->label(),
                'description' => $nextStatus->description(),
                'color' => $nextStatus->color(),
                'canTransition' => $this->canUserTransitionTo($nextStatus),
                'reason' => $this->getTransitionBlockReason($nextStatus),
            ];
        }

        return $availableStatuses;
    }

    /**
     * Check if current auth user can transition to this status.
     */
    private function canUserTransitionTo(Status $status): bool
    {
        $policy = new StatusChangePolicy();
        $authUser = auth()->user();

        return match ($status) {
            Status::VERIFIED => $policy->verify($authUser, $this->user),
            Status::RESTRICTED => $policy->restrict($authUser, $this->user),
            Status::SUSPENDED => $policy->suspend($authUser, $this->user),
            Status::ARCHIVED => $policy->archive($authUser, $this->user),
            default => true,
        };
    }

    /**
     * Get reason why transition is blocked (for tooltip).
     */
    private function getTransitionBlockReason(Status $status): ?string
    {
        if ($this->canUserTransitionTo($status)) {
            return null;
        }

        $authUser = auth()->user();

        return match ($status) {
            Status::VERIFIED => "Hanya Super Admin yang dapat memverifikasi akun admin",
            Status::PROTECTED => "Hanya Super Admin yang dapat menetapkan status terlindungi",
            Status::ARCHIVED => "Hanya Super Admin yang dapat mengarsipkan akun",
            default => "Anda tidak memiliki izin untuk transisi ini",
        };
    }

    /**
     * Validate and apply the status change.
     */
    public function transitionStatus(): void
    {
        $this->validate();

        try {
            $newStatus = Status::from($this->selectedStatus);
            $authUser = auth()->user();

            // Use the StatusTransitionService to apply the change
            $service = app(StatusTransitionService::class);
            $service->transition(
                user: $this->user,
                newStatus: $newStatus,
                reason: $this->adminNotes ?: "Status diubah oleh {$authUser->name}",
                triggeredBy: $authUser,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
                metadata: [
                    'previous_status' => $this->user->getStatus()?->value,
                    'admin_notes' => $this->adminNotes,
                ],
            );

            flash()->success(__('Status akun berhasil diperbarui'));
            $this->dispatch('statusChanged', userId: $this->user->id);
            $this->showTransitionForm = false;
            $this->adminNotes = '';

            // Refresh user data
            $this->user->refresh();
            $this->selectedStatus = $this->user->getStatus()?->value;
        } catch (\Exception $e) {
            flash()->error(__('Gagal mengubah status: ' . $e->getMessage()));
        }
    }

    /**
     * Get the current status info for display.
     */
    public function getCurrentStatusInfo(): array
    {
        $status = $this->user->getStatus();

        return [
            'value' => $status->value,
            'label' => $status->label(),
            'description' => $status->description(),
            'color' => $status->color(),
        ];
    }

    /**
     * Get days until auto-archive.
     */
    public function getDaysUntilAutoArchive(): int
    {
        return $this->user->daysUntilAutoArchive();
    }

    /**
     * Get days until auto-inactive.
     */
    public function getDaysUntilAutoInactive(): int
    {
        $lastActivity = $this->user->last_activity_at ?? $this->user->created_at;
        $daysIdle = $lastActivity->diffInDays(now());
        return max(0, 180 - $daysIdle);
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('status::livewire.status-selector', [
            'currentStatus' => $this->getCurrentStatusInfo(),
            'availableTransitions' => $this->getAvailableTransitions(),
            'daysUntilInactive' => $this->getDaysUntilAutoInactive(),
            'daysUntilArchive' => $this->getDaysUntilAutoArchive(),
        ]);
    }
}
