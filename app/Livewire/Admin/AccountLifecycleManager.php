<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\User\DetectUserAccountCloneAction;
use App\Actions\User\LockUserAccountAction;
use App\Actions\User\UnlockUserAccountAction;
use App\Models\User;
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
        $this->dispatch('notify', type: 'success', message: 'Account locked successfully.');
    }

    public function unlock(User $user, UnlockUserAccountAction $action): void
    {
        Gate::authorize('unlockAccount', $user);

        $action->execute($user);
        $this->dispatch('notify', type: 'success', message: 'Account unlocked successfully.');
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

        return view('livewire.admin.accounts.lifecycle', [
            'users' => $users,
        ]);
    }
}
