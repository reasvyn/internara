<?php

declare(strict_types=1);

namespace App\Auth\Password\Listeners;

use App\Auth\Notifications\CredentialChangedNotification;
use App\Auth\Password\Events\PasswordUpdated;
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
