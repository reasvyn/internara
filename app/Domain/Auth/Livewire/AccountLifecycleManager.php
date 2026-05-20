<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire;

use App\Domain\Auth\Actions\DetectUserAccountCloneAction;
use App\Domain\Auth\Actions\LockUserAccountAction;
use App\Domain\Auth\Actions\UnlockUserAccountAction;
use App\Domain\User\Models\User;
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
        flash()->success('Account locked successfully.');
    }

    public function unlock(User $user, UnlockUserAccountAction $action): void
    {
        Gate::authorize('unlockAccount', $user);

        $action->execute($user);
        flash()->success('Account unlocked successfully.');
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

        return view('auth.accounts.lifecycle', [
            'users' => $users,
        ]);
    }
}
