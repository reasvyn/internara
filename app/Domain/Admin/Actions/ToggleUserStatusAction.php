<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Notifications\AccountStatusNotification;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;

class ToggleUserStatusAction extends BaseAction
{
    public function execute(User $user, ?string $reason = null): User
    {
        if ($user->id === auth()->id()) {
            throw new \RuntimeException('Cannot change your own status.');
        }

        $currentStatus = $user->latestStatus()?->name;
        $newStatus = $currentStatus === AccountStatus::VERIFIED->value
            ? AccountStatus::SUSPENDED->value
            : AccountStatus::VERIFIED->value;

        $user->setStatus($newStatus, $reason ?? 'Toggled via User Manager');

        $user->notify(new AccountStatusNotification($newStatus, $reason ?? 'Updated by Administrator'));

        return $user;
    }
}
