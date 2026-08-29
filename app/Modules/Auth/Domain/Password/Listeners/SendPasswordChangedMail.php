<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Password\Listeners;

use App\Modules\Auth\Domain\Password\Events\PasswordUpdated;
use App\Modules\Auth\Notifications\CredentialChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendPasswordChangedMail implements ShouldQueue
{
    public function handle(PasswordUpdated $event): void
    {
        $user = $event->user;

        if ($user->email) {
            $user->notify(new CredentialChangedNotification('password'));
        }
    }
}
