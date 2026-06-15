<?php

declare(strict_types=1);

namespace App\User\AccountStatus\Livewire;

use App\Core\Exceptions\RejectedException;
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

    public bool $showConfirm = false;

    public string $confirmActionType = '';

    public ?string $confirmTarget = null;

    public string $confirmMessage = '';

    public function askLock(string $id): void
    {
        $this->confirmActionType = 'lock';
        $this->confirmTarget = $id;
        $this->confirmMessage = __('Lock this account?');
        $this->showConfirm = true;
    }

    public function askUnlock(string $id): void
    {
        $this->confirmActionType = 'unlock';
        $this->confirmTarget = $id;
        $this->confirmMessage = __('Unlock this account?');
        $this->showConfirm = true;
    }

    public function confirmAction(
        LockUserAccountAction $lockAction,
        UnlockUserAccountAction $unlockAction,
    ): void {
        try {
            $user = User::findOrFail($this->confirmTarget);
            $this->authorize('update', $user);

            if ($this->confirmActionType === 'lock') {
                Gate::authorize('lockAccount', $user);
                $lockAction->execute($user);
                flash()->success(__('auth.account_locked'));
            } elseif ($this->confirmActionType === 'unlock') {
                Gate::authorize('unlockAccount', $user);
                $unlockAction->execute($user);
                flash()->success(__('auth.account_unlocked'));
            }
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmActionType = '';
        $this->confirmMessage = '';
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
