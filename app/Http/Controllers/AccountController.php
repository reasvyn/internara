<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\User\Actions\DetectUserAccountCloneAction;
use App\Domain\User\Actions\LockUserAccountAction;
use App\Domain\User\Actions\UnlockUserAccountAction;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewLifecycleDashboard', User::class);

        $users = User::with('statuses')->latest()->paginate(20);

        return view('livewire.admin.accounts.lifecycle', [
            'users' => $users,
        ]);
    }

    public function lock(User $user, LockUserAccountAction $action)
    {
        Gate::authorize('lockAccount', $user);

        $action->execute($user);

        return back()->with('success', 'Account locked successfully.');
    }

    public function unlock(User $user, UnlockUserAccountAction $action)
    {
        Gate::authorize('unlockAccount', $user);

        $action->execute($user);

        return back()->with('success', 'Account unlocked successfully.');
    }

    public function detectClones(DetectUserAccountCloneAction $action)
    {
        Gate::authorize('viewLifecycleDashboard', User::class);

        $clones = $action->execute();

        return view('livewire.admin.accounts.clones', [
            'clones' => $clones,
        ]);
    }
}
