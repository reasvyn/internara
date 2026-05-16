<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\Auth\AccountStatus;
use App\Models\User;
use App\Notifications\User\AccountStatusNotification;

class ToggleUserStatusAction
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
