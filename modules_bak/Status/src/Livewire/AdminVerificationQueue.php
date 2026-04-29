<?php

declare(strict_types=1);

namespace Modules\Status\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Status\Enums\Status;
use Modules\Status\Services\StatusTransitionService;
use Modules\User\Models\User;

/**
 * AdminVerificationQueueComponent
 *
 * Dedicated interface for bulk account verification.
 * Shows pending ACTIVATED accounts across all roles, allows batch verification.
 * Uses Spatie Model Status for account lifecycle management.
 */
class AdminVerificationQueue extends Component
{
    use WithPagination;

    public int $perPage = 15;

    public string $searchQuery = '';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public string $roleFilter = ''; // Filter by role (student, teacher, mentor, admin, all)

    public array $selectedUsers = [];

    public array $notes = []; // user_id => note

    public bool $showBulkActionsBar = false;

    private StatusTransitionService $statusTransitionService;

    protected $listeners = [
        'verify-user' => 'verifyUser',
        'reject-user' => 'rejectUser',
        'bulk-verify' => 'bulkVerify',
    ];

    public function mount(StatusTransitionService $statusTransitionService): void
    {
        $this->statusTransitionService = $statusTransitionService;
    }

    public function render()
    {
        $pendingUsers = $this->getPendingVerifications();
        $totalPending = $this->countPendingVerifications();

        return view('livewire.admin-verification-queue', [
            'pendingUsers' => $pendingUsers,
            'totalPending' => $totalPending,
            'selectedCount' => count($this->selectedUsers),
            'showBulkActionsBar' => count($this->selectedUsers) > 0,
            'roles' => [
                'student' => __('status::roles.student'),
                'teacher' => __('status::roles.teacher'),
                'mentor' => __('status::roles.mentor'),
                'admin' => __('status::roles.admin'),
            ],
        ]);
    }

    /**
     * Count pending verifications (ACTIVATED status accounts)
     * Uses spatie status_histories table to find users with ACTIVATED status
     */
    private function countPendingVerifications(): int
    {
        return User::whereHas('statuses', function ($query) {
            $query->where('name', Status::ACTIVATED->value);
        })->count();
    }

    /**
     * Get pending verifications with search, filtering, and sorting
     * Queries using spatie status relationship
     */
    private function getPendingVerifications()
    {
        $query = User::whereHas('statuses', function ($q) {
            $q->where('name', Status::ACTIVATED->value);
        });

        // Filter by role
        if ($this->roleFilter && $this->roleFilter !== 'all') {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->roleFilter);
            });
        }

        // Search by email, name, phone
        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('email', 'like', "%{$this->searchQuery}%")
                    ->orWhere('name', 'like', "%{$this->searchQuery}%")
                    ->orWhere('phone', 'like', "%{$this->searchQuery}%");
            });
        }

        // Sort
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    /**
     * Toggle user selection for bulk actions
     */
    public function toggleUserSelection(int $userId): void
    {
        if (in_array($userId, $this->selectedUsers)) {
            $this->selectedUsers = array_filter($this->selectedUsers, fn($id) => $id !== $userId);
        } else {
            $this->selectedUsers[] = $userId;
        }

        $this->showBulkActionsBar = count($this->selectedUsers) > 0;
    }

    /**
     * Select all pending users on current page
     */
    public function selectAll(): void
    {
        $userIds = $this->getPendingVerifications()->pluck('id')->toArray();

        $this->selectedUsers = array_unique(array_merge($this->selectedUsers, $userIds));
        $this->showBulkActionsBar = true;
    }

    /**
     * Clear all selections
     */
    public function clearSelections(): void
    {
        $this->selectedUsers = [];
        $this->notes = [];
        $this->showBulkActionsBar = false;
    }

    /**
     * Verify individual user
     */
    public function verifyUser(int $userId): void
    {
        $user = User::findOrFail($userId);
        $note = $this->notes[$userId] ?? null;

        try {
            // Use spatie status() method to set status
            $user->setStatus(Status::VERIFIED->value, $note ?? 'Verified by admin');

            $this->dispatch(
                'notify',
                type: 'success',
                message: "✅ {$user->email} verified successfully",
            );

            // Clear note for this user
            unset($this->notes[$userId]);

            // Remove from selection if present
            $this->toggleUserSelection($userId);

            // Refresh pagination
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch(
                'notify',
                type: 'error',
                message: "❌ Error verifying {$user->email}: {$e->getMessage()}",
            );
        }
    }

    /**
     * Reject user account (move to RESTRICTED or SUSPENDED)
     */
    public function rejectUser(int $userId, string $targetStatus = 'suspended'): void
    {
        $user = User::findOrFail($userId);
        $note = $this->notes[$userId] ?? null;
        $status =
            $targetStatus === 'restricted' ? Status::RESTRICTED->value : Status::SUSPENDED->value;

        try {
            $user->setStatus($status, $note ?? 'Account rejected during verification');

            $this->dispatch(
                'notify',
                type: 'warning',
                message: "⛔ {$user->email} {$targetStatus}",
            );

            unset($this->notes[$userId]);
            $this->toggleUserSelection($userId);
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch(
                'notify',
                type: 'error',
                message: "❌ Error rejecting {$user->email}: {$e->getMessage()}",
            );
        }
    }

    /**
     * Verify selected users in bulk
     */
    public function bulkVerify(): void
    {
        if (empty($this->selectedUsers)) {
            $this->dispatch('notify', type: 'warning', message: 'No users selected');

            return;
        }

        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($this->selectedUsers as $userId) {
            try {
                $user = User::findOrFail($userId);
                $note = $this->notes[$userId] ?? null;

                $user->setStatus(Status::VERIFIED->value, $note ?? 'Verified via bulk action');

                $successCount++;
            } catch (\Exception $e) {
                $failureCount++;
                $errors[] = "User {$userId}: {$e->getMessage()}";
            }
        }

        $message = "✅ Verified {$successCount} accounts";
        if ($failureCount > 0) {
            $message .= ", ❌ {$failureCount} failed";
        }

        $this->dispatch(
            'notify',
            type: $failureCount > 0 ? 'warning' : 'success',
            message: $message,
        );

        $this->clearSelections();
        $this->resetPage();
    }

    /**
     * Update search query with debounce
     */
    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    /**
     * Change sorting
     */
    public function sort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Export pending users list to CSV
     */
    public function exportPendingUsers()
    {
        $users = User::whereHas('statuses', function ($q) {
            $q->where('name', Status::ACTIVATED->value);
        })
            ->orderBy('created_at', 'desc')
            ->get(['id', 'email', 'name', 'phone', 'created_at']);

        $csv = "Email,Name,Phone,Pending Since\n";
        foreach ($users as $user) {
            $csv .= "\"{$user->email}\",\"{$user->name}\",\"{$user->phone}\",{$user->created_at->toDateString()}\n";
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'pending-verifications-' . now()->format('Y-m-d') . '.csv');
    }
}
