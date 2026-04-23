<?php

declare(strict_types=1);

namespace Modules\Status\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Status\Enums\Status;
use Modules\Status\Services\RoleBasedStatusTransitionService;
use Modules\User\Models\User;

/**
 * StatusSelectorComponent (Refactored)
 *
 * Role-aware status transition component using RADIO BUTTONS.
 * Shows only valid transitions for user's specific role.
 *
 * **Radio Button Design:**
 * - Each status as a separate radio option
 * - Visual badge showing status color
 * - Description text explaining what status means
 * - Disabled state if not allowed by role
 * - Admin notes textarea for reason
 *
 * **Role-Specific Behavior:**
 * - Student: Show ACTIVATED, VERIFIED, RESTRICTED, SUSPENDED
 * - Teacher: Show VERIFIED, RESTRICTED, SUSPENDED
 * - Mentor: Show RESTRICTED, SUSPENDED
 * - Admin: Show VERIFIED, RESTRICTED, SUSPENDED, INACTIVE
 * - SuperAdmin: PROTECTED (no transitions allowed)
 */
class StatusSelector extends Component
{
    public User $user;

    public ?string $selectedStatus = null;

    public string $adminNotes = '';

    public bool $showTransitionForm = false;

    public bool $isLoading = false;

    public string $errorMessage = '';

    private RoleBasedStatusTransitionService $transitionService;

    protected $rules = [
        'selectedStatus' => 'required|string',
        'adminNotes' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'selectedStatus.required' => 'Silakan pilih status akun yang akan diterapkan',
        'adminNotes.max' => 'Catatan tidak boleh melebihi 500 karakter',
    ];

    /**
     * Mount the component with user and service
     */
    public function mount(User $user, RoleBasedStatusTransitionService $transitionService): void
    {
        $this->user = $user;
        $this->transitionService = $transitionService;

        // Set current status
        $currentStatus = $this->user->latestStatus()?->name ?? Status::PENDING->value;
        $this->selectedStatus = $currentStatus;

        // Authorization check
        abort_unless(auth()->check(), 403);
        abort_unless($this->canManageUser(), 403);
    }

    /**
     * Check if current auth user can manage this user's status
     */
    private function canManageUser(): bool
    {
        $authUser = auth()->user();
        $authRole = $authUser->getHighestRole();
        $userRole = $this->user->getHighestRole();

        // SuperAdmin can manage anyone except other SuperAdmins
        if ($authRole === 'super_admin') {
            return $userRole !== 'super_admin' || $authUser->id === $this->user->id;
        }

        // Admin can manage students/teachers/mentors
        if ($authRole === 'admin') {
            return in_array($userRole, ['student', 'teacher', 'mentor']);
        }

        // Teacher can manage students
        if ($authRole === 'teacher') {
            return $userRole === 'student';
        }

        // Users can manage their own account
        return $authUser->id === $this->user->id;
    }

    /**
     * Get available status transitions for this user's role
     * Returns radio button options with metadata
     */
    public function getAvailableTransitions(): array
    {
        $currentStatus = Status::from($this->selectedStatus);
        $transitions = $this->transitionService->getValidTransitionsForRole($this->user);

        $options = [];
        foreach ($transitions as $status) {
            $authUser = auth()->user();
            $canTransition = $this->transitionService->canTransition(
                $this->user,
                $currentStatus,
                $status,
                $authUser
            );

            $options[] = [
                'status' => $status,
                'value' => $status->value,
                'label' => __($status->label()),
                'description' => $status->description(),
                'color' => $status->color(),
                'canSelect' => $canTransition,
                'blockReason' => !$canTransition ? "Anda tidak memiliki izin untuk transisi ini ({$authUser->getHighestRole()})" : null,
            ];
        }

        return $options;
    }

    /**
     * Get current status info
     */
    public function getCurrentStatus(): array
    {
        $status = Status::from($this->selectedStatus);

        return [
            'value' => $status->value,
            'label' => __($status->label()),
            'description' => $status->description(),
            'color' => $status->color(),
            'isProtected' => $status->isProtected(),
        ];
    }

    /**
     * Apply status transition
     */
    public function transitionStatus(): void
    {
        $this->errorMessage = '';
        $this->isLoading = true;

        try {
            $this->validate();

            $newStatus = Status::from($this->selectedStatus);
            $currentStatus = Status::from($this->user->latestStatus()?->name ?? Status::PENDING->value);
            $authUser = auth()->user();

            // Validate transition
            if (!$this->transitionService->canTransition(
                $this->user,
                $currentStatus,
                $newStatus,
                $authUser
            )) {
                throw new \Exception("Transisi tidak diizinkan untuk role Anda");
            }

            // Perform transition
            $this->transitionService->transition(
                user: $this->user,
                toStatus: $newStatus,
                reason: $this->adminNotes ?: null,
                triggeredBy: $authUser,
            );

            flash()->success(__("Status akun berhasil diperbarui"));
            $this->dispatch('statusChanged', userId: $this->user->id);
            $this->showTransitionForm = false;
            $this->adminNotes = '';
            $this->isLoading = false;

            // Refresh user
            $this->user->refresh();
            $this->selectedStatus = $this->user->latestStatus()?->name ?? Status::PENDING->value;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->isLoading = false;
        }
    }

    /**
     * Render the component
     */
    public function render(): View
    {
        $currentStatus = $this->getCurrentStatus();
        $canManage = !$currentStatus['isProtected'] && $this->canManageUser();

        return view('status::livewire.status-selector', [
            'currentStatus' => $currentStatus,
            'availableTransitions' => $this->getAvailableTransitions(),
            'canManage' => $canManage,
            'userRole' => $this->user->getHighestRole(),
            'authRole' => auth()->user()->getHighestRole(),
        ]);
    }
}
