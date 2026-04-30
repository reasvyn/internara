<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AccountLifecycle\DetectAccountCloneAction;
use App\Actions\AccountLifecycle\LockAccountAction;
use App\Actions\AccountLifecycle\UnlockAccountAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountLifecycleController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewLifecycleDashboard', User::class);

        $users = User::with('statuses')
            ->latest()
            ->paginate(20);

        return view('livewire.admin.accounts.lifecycle', [
            'users' => $users,
        ]);
    }

    public function lock(User $user, LockAccountAction $action)
    {
        Gate::authorize('lockAccount', $user);

        $action->execute($user);

        return back()->with('success', 'Account locked successfully.');
    }

    public function unlock(User $user, UnlockAccountAction $action)
    {
        Gate::authorize('unlockAccount', $user);

        $action->execute($user);

        return back()->with('success', 'Account unlocked successfully.');
    }

    public function detectClones(DetectAccountCloneAction $action)
    {
        Gate::authorize('viewLifecycleDashboard', User::class);

        $clones = $action->execute();

        return view('livewire.admin.accounts.clones', [
            'clones' => $clones,
        ]);
    }
}
