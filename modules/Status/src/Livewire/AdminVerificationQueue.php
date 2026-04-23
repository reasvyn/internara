<?php

declare(strict_types=1);

namespace Modules\Status\Livewire;

use Modules\User\Models\User;
use Modules\Status\Enums\AccountStatus;
use Modules\Status\Services\StatusTransitionService;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

/**
 * AdminVerificationQueueComponent
 *
 * Dedicated interface for bulk account verification.
 * Shows pending ACTIVATED accounts, allows batch verification with per-account notes.
 */
class AdminVerificationQueue extends Component
{
    use WithPagination;

    public int $perPage = 15;
    public string $searchQuery = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
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

        return view('livewire.admin-verification-queue', [
            'pendingUsers' => $pendingUsers,
            'totalPending' => User::where('account_status', AccountStatus::ACTIVATED->value)->count(),
            'selectedCount' => count($this->selectedUsers),
            'showBulkActionsBar' => count($this->selectedUsers) > 0,
        ]);
    }

    /**
     * Get pending verifications with search and sorting
     */
    private function getPendingVerifications()
    {
        $query = User::where('account_status', AccountStatus::ACTIVATED->value);

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
            $this->selectedUsers = array_filter(
                $this->selectedUsers,
                fn($id) => $id !== $userId
            );
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
        $userIds = $this->getPendingVerifications()
            ->pluck('id')
            ->toArray();

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
            $this->statusTransitionService->transition(
                user: $user,
                targetStatus: AccountStatus::VERIFIED,
                reason: $note ?? 'Verified by admin',
                triggeredByUser: auth()->user(),
            );

            $this->dispatch('notify', type: 'success', message: "✅ {$user->email} verified successfully");

            // Clear note for this user
            unset($this->notes[$userId]);

            // Remove from selection if present
            $this->toggleUserSelection($userId);

            // Refresh pagination
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: "❌ Error verifying {$user->email}: {$e->getMessage()}");
        }
    }

    /**
     * Reject user account (move to RESTRICTED or SUSPENDED)
     */
    public function rejectUser(int $userId, string $targetStatus = 'suspended'): void
    {
        $user = User::findOrFail($userId);
        $note = $this->notes[$userId] ?? null;
        $status = $targetStatus === 'restricted' ? AccountStatus::RESTRICTED : AccountStatus::SUSPENDED;

        try {
            $this->statusTransitionService->transition(
                user: $user,
                targetStatus: $status,
                reason: $note ?? "Account rejected during verification",
                triggeredByUser: auth()->user(),
            );

            $this->dispatch('notify', type: 'warning', message: "⛔ {$user->email} {$targetStatus}");

            unset($this->notes[$userId]);
            $this->toggleUserSelection($userId);
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: "❌ Error rejecting {$user->email}: {$e->getMessage()}");
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

                $this->statusTransitionService->transition(
                    user: $user,
                    targetStatus: AccountStatus::VERIFIED,
                    reason: $note ?? 'Verified via bulk action',
                    triggeredByUser: auth()->user(),
                );

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

        $this->dispatch('notify', type: $failureCount > 0 ? 'warning' : 'success', message: $message);

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
        $users = User::where('account_status', AccountStatus::ACTIVATED->value)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'email', 'name', 'phone', 'created_at']);

        $csv = "Email,Name,Phone,Pending Since\n";
        foreach ($users as $user) {
            $csv .= "\"{$user->email}\",\"{$user->name}\",\"{$user->phone}\",{$user->created_at->toDateString()}\n";
        }

        return response()->streamDownload(
            function () use ($csv) {
                echo $csv;
            },
            'pending-verifications-'.now()->format('Y-m-d').'.csv'
        );
    }
}
