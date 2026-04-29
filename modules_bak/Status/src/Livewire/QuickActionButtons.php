<?php

declare(strict_types=1);

namespace Modules\Status\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Status\Enums\Status;
use Modules\Status\Services\AccountLockoutService;
use Modules\Status\Services\StatusTransitionService;
use Modules\User\Models\User;

/**
 * Quick action buttons for rapid account status management.
 * Provides one-click actions for common operations.
 */
class QuickActionButtons extends Component
{
    public User $user;

    public bool $showConfirm = false;

    public ?string $pendingAction = null;

    /**
     * Mount the component.
     */
    public function mount(User $user): void
    {
        $this->user = $user;

        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->can('transition', $this->user), 403);
    }

    /**
     * Quick verify action.
     */
    public function quickVerify(): void
    {
        try {
            $service = app(StatusTransitionService::class);
            $service->transition(
                user: $this->user,
                newStatus: Status::VERIFIED,
                reason: __('status::quick_actions.verify_reason', ['name' => auth()->user()->name]),
                triggeredBy: auth()->user(),
                ipAddress: request()->ip(),
            );

            flash()->success(__('Akun berhasil diverifikasi'));
            $this->dispatch('accountUpdated', userId: $this->user->id);
            $this->user->refresh();
        } catch (\Exception $e) {
            flash()->error(__('Gagal memverifikasi: ' . $e->getMessage()));
        }
    }

    /**
     * Quick suspend action.
     */
    public function quickSuspend(): void
    {
        try {
            $service = app(StatusTransitionService::class);
            $service->transition(
                user: $this->user,
                newStatus: Status::SUSPENDED,
                reason: __('status::quick_actions.suspend_reason', ['name' => auth()->user()->name]),
                triggeredBy: auth()->user(),
                ipAddress: request()->ip(),
            );

            flash()->success(__('Akun berhasil disuspensi'));
            $this->dispatch('accountUpdated', userId: $this->user->id);
            $this->user->refresh();
        } catch (\Exception $e) {
            flash()->error(__('Gagal menyuspensi: ' . $e->getMessage()));
        }
    }

    /**
     * Quick unlock (clear lockout).
     */
    public function quickUnlock(): void
    {
        try {
            $service = app(AccountLockoutService::class);
            $service->unlockAccount($this->user, auth()->user(), __('status::quick_actions.unlock_reason'));

            flash()->success(__('Akun berhasil dibuka'));
            $this->dispatch('accountUpdated', userId: $this->user->id);
            $this->user->refresh();
        } catch (\Exception $e) {
            flash()->error(__('Gagal membuka akun: ' . $e->getMessage()));
        }
    }

    /**
     * Get available quick actions.
     */
    public function getAvailableActions(): array
    {
        $actions = [];

        // Verify action - available if ACTIVATED
        if ($this->user->getStatus() === Status::ACTIVATED) {
            $actions[] = [
                'id' => 'verify',
                'label' => 'Verifikasi',
                'color' => 'green',
                'icon' => 'check',
                'confirm' => false,
            ];
        }

        // Suspend action - available if not SUSPENDED/PROTECTED/ARCHIVED
        if (
            !in_array($this->user->getStatus(), [
                Status::SUSPENDED,
                Status::PROTECTED,
                Status::ARCHIVED,
            ])
        ) {
            $actions[] = [
                'id' => 'suspend',
                'label' => 'Suspensi',
                'color' => 'red',
                'icon' => 'ban',
                'confirm' => true,
            ];
        }

        // Unlock action - available if locked out
        $lockoutService = app(AccountLockoutService::class);
        if ($lockoutService->isLockedOut($this->user)) {
            $actions[] = [
                'id' => 'unlock',
                'label' => 'Buka Kunci',
                'color' => 'blue',
                'icon' => 'unlock',
                'confirm' => false,
            ];
        }

        return $actions;
    }

    /**
     * Execute quick action with confirmation if needed.
     */
    public function executeAction(string $actionId): void
    {
        match ($actionId) {
            'verify' => $this->quickVerify(),
            'suspend' => $this->quickSuspend(),
            'unlock' => $this->quickUnlock(),
            default => flash()->error(__('Aksi tidak dikenali')),
        };
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('status::livewire.quick-action-buttons', [
            'availableActions' => $this->getAvailableActions(),
        ]);
    }
}
