<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Profile\Listeners;

use App\Modules\Auth\Notifications\CredentialChangedNotification;
use App\Modules\User\Models\User;
use App\Modules\User\Domain\Profile\Events\ProfileUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendProfileChangedMail implements ShouldQueue
{
    public function handle(ProfileUpdated $event): void
    {
        $profile = $event->profile;
        $user = User::find($profile->user_id);

        if ($user === null || ! $user->email) {
            return;
        }

        $emailChanged = $event->previousEmail !== null && $event->previousEmail !== $user->email;
        $usernameChanged =
            $event->previousUsername !== null && $event->previousUsername !== $user->username;

        if ($emailChanged) {
            $user->notify(new CredentialChangedNotification('email'));
        }

        if ($usernameChanged) {
            $user->notify(new CredentialChangedNotification('username'));
        }
    }
}
