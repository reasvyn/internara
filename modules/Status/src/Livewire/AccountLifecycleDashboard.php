<?php

declare(strict_types=1);

namespace Modules\Status\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Status\Enums\AccountStatus;
use Modules\User\Models\User;

/**
 * Livewire component for admin account lifecycle dashboard.
 *
 * Provides overview of all account statuses with pending actions:
 * - Pending verification queue
 * - Suspended accounts
 * - Locked out accounts
 * - Idle accounts approaching auto-transition
 * - Recent status changes
 */
class AccountLifecycleDashboard extends Component
{
    public array $statusStats = [];

    public array $pendingActions = [];

    public int $totalUsers = 0;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        // Check authorization
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->role === 'super_admin' || auth()->user()->role === 'admin', 403);

        $this->loadStats();
    }

    /**
     * Load dashboard statistics.
     */
    public function loadStats(): void
    {
        $this->totalUsers = User::count();

        // Count users by status
        $this->statusStats = [
            'provisioned' => User::where('account_status', AccountStatus::PROVISIONED->value)->count(),
            'activated' => User::where('account_status', AccountStatus::ACTIVATED->value)->count(),
            'verified' => User::where('account_status', AccountStatus::VERIFIED->value)->count(),
            'protected' => User::where('account_status', AccountStatus::PROTECTED->value)->count(),
            'restricted' => User::where('account_status', AccountStatus::RESTRICTED->value)->count(),
            'suspended' => User::where('account_status', AccountStatus::SUSPENDED->value)->count(),
            'inactive' => User::where('account_status', AccountStatus::INACTIVE->value)->count(),
            'archived' => User::where('account_status', AccountStatus::ARCHIVED->value)->count(),
        ];
    }

    /**
     * Get users pending verification (in ACTIVATED state).
     */
    public function getPendingVerificationQueue(): array
    {
        return User::where('account_status', AccountStatus::ACTIVATED->value)
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'createdAt' => $user->created_at->diffForHumans(),
                'daysWaiting' => $user->created_at->diffInDays(now()),
            ])
            ->toArray();
    }

    /**
     * Get suspended accounts.
     */
    public function getSuspendedAccounts(): array
    {
        return User::where('account_status', AccountStatus::SUSPENDED->value)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'suspendedAt' => $user->updated_at->format('Y-m-d H:i'),
                'daysSuspended' => $user->updated_at->diffInDays(now()),
            ])
            ->toArray();
    }

    /**
     * Get locked out accounts (with active login_lockout restriction).
     */
    public function getLockedOutAccounts(): array
    {
        return User::whereHas('restrictions', function ($q) {
            $q->where('restriction_type', 'login_lockout')
                ->where('is_active', true)
                ->where(function ($q2) {
                    $q2->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        })
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'lockedOutAt' => $user->restrictions()
                    ->where('restriction_type', 'login_lockout')
                    ->where('is_active', true)
                    ->first()
                    ?->applied_at?->format('Y-m-d H:i'),
                'expiresAt' => $user->restrictions()
                    ->where('restriction_type', 'login_lockout')
                    ->where('is_active', true)
                    ->first()
                    ?->expires_at?->format('Y-m-d H:i'),
            ])
            ->toArray();
    }

    /**
     * Get accounts approaching INACTIVE (170+ days idle).
     */
    public function getIdleApproachingInactive(): array
    {
        $cutoffDate = now()->subDays(170);

        return User::where('account_status', AccountStatus::VERIFIED->value)
            ->where(function ($q) use ($cutoffDate) {
                $q->where('last_activity_at', '<', $cutoffDate)
                    ->orWhereNull('last_activity_at');
            })
            ->orderBy('last_activity_at', 'asc')
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'lastActivityAt' => $user->last_activity_at?->format('Y-m-d H:i') ?? 'Never',
                'daysUntilInactive' => max(0, 180 - ($user->last_activity_at ?? $user->created_at)->diffInDays(now())),
            ])
            ->toArray();
    }

    /**
     * Get recent status changes.
     */
    public function getRecentChanges(): array
    {
        return \Modules\Status\Models\AccountStatusHistory::orderByDesc('created_at')
            ->limit(10)
            ->with('user', 'triggeredBy')
            ->get()
            ->map(fn ($history) => [
                'id' => $history->id,
                'userId' => $history->user_id,
                'userName' => $history->user->name,
                'oldStatus' => $history->old_status,
                'newStatus' => $history->new_status,
                'reason' => $history->reason,
                'triggeredBy' => $history->triggeredBy?->name ?? 'System',
                'changedAt' => $history->created_at->diffForHumans(),
                'timestamp' => $history->created_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();
    }

    /**
     * Navigate to user detail page.
     */
    public function viewUser(string $userId): void
    {
        $this->redirect(route('admin.users.show', $userId));
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('status::livewire.account-lifecycle-dashboard', [
            'statusStats' => $this->statusStats,
            'totalUsers' => $this->totalUsers,
            'pendingVerification' => $this->getPendingVerificationQueue(),
            'suspendedAccounts' => $this->getSuspendedAccounts(),
            'lockedOutAccounts' => $this->getLockedOutAccounts(),
            'idleApproachingInactive' => $this->getIdleApproachingInactive(),
            'recentChanges' => $this->getRecentChanges(),
        ]);
    }
}
