<?php

declare(strict_types=1);

namespace App\Auth\Login\Listeners;

use App\Auth\Login\Events\LoginSucceeded;
use App\Core\Contracts\SendsNotifications;

class SendSuperAdminWelcomeNotification
{
    public function __construct(
        protected SendsNotifications $sendNotification,
    ) {}

    public function handle(LoginSucceeded $event): void
    {
        $user = $event->user;

        if (! $user->hasRole('superadmin') || $user->first_login_at !== null) {
            return;
        }

        $this->sendNotification->execute(
            userId: $user->id,
            type: 'welcome',
            title: __('notifications.welcome_to_dashboard.title'),
            message: __('notifications.welcome_to_dashboard.message'),
            link: route('sysadmin.dashboard'),
        );

        $user->update(['first_login_at' => now()]);
    }
}
