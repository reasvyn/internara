<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Livewire\Concerns;

use App\Modules\Auth\Domain\AccessTokens\Models\AccessToken;
use App\Modules\User\Domain\UserManagement\Notifications\ActivationCodeNotification;
use App\Modules\User\Models\User;
use TallStackUi\Traits\Interactions;

trait DownloadsAccountSlips
{
    use Interactions;

    public bool $showAccountSlip = false;

    public ?User $slipUser = null;

    public string $slipCode = '';

    public function showSlip(string $id): void
    {
        $this->slipUser = User::findOrFail($id);
        $this->slipCode = AccessToken::generateFor($this->slipUser, 'activation', ['name' => 'Account Activation'])['plain_text'];
        $this->showAccountSlip = true;
    }

    public function regenerateCode(): void
    {
        if (! $this->slipUser) {
            return;
        }

        $this->slipCode = AccessToken::generateFor($this->slipUser, 'activation', ['name' => 'Account Activation'])['plain_text'];
        $this->toast()->success(__('user.manager.code_regenerated'))->send();
    }

    public function sendCode(): void
    {
        if (! $this->slipUser || $this->slipCode === '') {
            return;
        }

        $this->slipUser->notify(new ActivationCodeNotification($this->slipUser, $this->slipCode));
        $this->toast()->success(__('user.manager.code_sent'))->send();
    }

    public function downloadSlip(): void
    {
        if (! $this->slipUser) {
            return;
        }

        $this->redirect(route('admin.users.account-slip', $this->slipUser));
    }

    public function downloadSelectedSlips(): void
    {
        if ($this->selectedIds === []) {
            $this->toast()->warning(__('common.actions.no_records_selected'))->send();

            return;
        }

        $this->redirect(
            route('admin.users.account-slips.batch', [
                'ids' => implode(',', $this->selectedIds),
            ]),
        );
    }
}
