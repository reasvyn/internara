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
use TallStackUi\Traits\Interactions;

class AccountLifecycleManager extends Component
{
    use Interactions;
    use WithPagination;

    public bool $showClones = false;

    public string $confirmActionType = '';

    public ?string $confirmTarget = null;

    public string $confirmMessage = '';

    public function askLock(string $id): void
    {
        $this->confirmActionType = 'lock';
        $this->confirmTarget = $id;
        $this->confirmMessage = __('Lock this account?');
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function askUnlock(string $id): void
    {
        $this->confirmActionType = 'unlock';
        $this->confirmTarget = $id;
        $this->confirmMessage = __('Unlock this account?');
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
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
                $this->toast()->success(__('auth.account_locked'))->send();
            } elseif ($this->confirmActionType === 'unlock') {
                Gate::authorize('unlockAccount', $user);
                $unlockAction->execute($user);
                $this->toast()->success(__('auth.account_unlocked'))->send();
            }
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
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

        $users = User::latest()->paginate(20);

        return view('user.account-status.accounts.lifecycle', [
            'users' => $users,
        ]);
    }
}
