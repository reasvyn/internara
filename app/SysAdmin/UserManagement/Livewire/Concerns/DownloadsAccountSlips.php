<?php

declare(strict_types=1);

namespace App\SysAdmin\UserManagement\Livewire\Concerns;

use App\Auth\ApiTokens\Models\ApiToken;
use App\SysAdmin\UserManagement\Notifications\ActivationCodeNotification;
use App\User\Models\User;

trait DownloadsAccountSlips
{
    public bool $showAccountSlip = false;

    public ?User $slipUser = null;

    public string $slipCode = '';

    public function showSlip(string $id): void
    {
        $this->slipUser = User::findOrFail($id);
        $this->slipCode = ApiToken::generateFor($this->slipUser, 'activation', ['name' => 'Account Activation'])['plain_text'];
        $this->showAccountSlip = true;
    }

    public function regenerateCode(): void
    {
        if (! $this->slipUser) {
            return;
        }

        $this->slipCode = ApiToken::generateFor($this->slipUser, 'activation', ['name' => 'Account Activation'])['plain_text'];
        flash()->success(__('user.manager.code_regenerated'));
    }

    public function sendCode(): void
    {
        if (! $this->slipUser || $this->slipCode === '') {
            return;
        }

        $this->slipUser->notify(new ActivationCodeNotification($this->slipUser, $this->slipCode));
        flash()->success(__('user.manager.code_sent'));
    }

    public function downloadSlip(): void
    {
        if (! $this->slipUser) {
            return;
        }

        $this->redirect(route('sysadmin.users.account-slip', $this->slipUser));
    }

    public function downloadSelectedSlips(): void
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return;
        }

        $this->redirect(
            route('sysadmin.users.account-slips.batch', [
                'ids' => implode(',', $this->selectedIds),
            ]),
        );
    }
}
