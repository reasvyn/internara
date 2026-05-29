<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire\Concerns;

use App\Domain\User\Models\User;

trait DownloadsAccountSlips
{
    public function downloadAccountSlip(string $id): void
    {
        $user = User::findOrFail($id);

        $this->redirect(route('admin.users.account-slip', $user));
    }

    public function downloadSelectedSlips(): void
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return;
        }

        $this->redirect(route('admin.users.account-slips.batch', ['ids' => implode(',', $this->selectedIds)]));
    }
}
