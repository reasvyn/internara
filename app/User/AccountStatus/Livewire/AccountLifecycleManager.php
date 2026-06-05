<?php

declare(strict_types=1);

namespace App\User\AccountStatus\Livewire;

use App\User\AccountStatus\Actions\DetectUserAccountCloneAction;
use App\User\AccountStatus\Actions\LockUserAccountAction;
use App\User\AccountStatus\Actions\UnlockUserAccountAction;
use App\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AccountLifecycleManager extends Component
{
    use WithPagination;

    public bool $showClones = false;

    public function lock(User $user, LockUserAccountAction $action): void
    {
        Gate::authorize('lockAccount', $user);

        $action->execute($user);
        flash()->success(__('auth.account_locked'));
    }

    public function unlock(User $user, UnlockUserAccountAction $action): void
    {
        Gate::authorize('unlockAccount', $user);

        $action->execute($user);
        flash()->success(__('auth.account_unlocked'));
    }

    public function detectClones(DetectUserAccountCloneAction $action): array
    {
        Gate::authorize('viewLifecycleDashboard', User::class);

        return $action->execute();
    }

    public function render(): View
    {
        Gate::authorize('viewLifecycleDashboard', User::class);

        $users = User::with('statuses')->latest()->paginate(20);

        return view('user.account-status.accounts.lifecycle', [
            'users' => $users,
        ]);
    }
}
