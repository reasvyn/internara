<?php

declare(strict_types=1);

namespace App\Auth\Login\Listeners;

use App\Auth\Login\Events\LoginSucceeded;
use App\Core\Contracts\SendsNotifications;

class SendSuperAdminWelcomeNotification
{
    protected array $roleWelcomeMap = [
        'superadmin' => 'notifications.welcome_to_dashboard.super_admin',
        'admin' => 'notifications.welcome_to_dashboard.admin',
        'student' => 'notifications.welcome_to_dashboard.student',
        'teacher' => 'notifications.welcome_to_dashboard.teacher',
        'supervisor' => 'notifications.welcome_to_dashboard.supervisor',
    ];

    public function __construct(
        protected SendsNotifications $sendNotification,
    ) {}

    public function handle(LoginSucceeded $event): void
    {
        $user = $event->user;

        if ($user->first_login_at !== null) {
            return;
        }

        $role = collect(array_keys($this->roleWelcomeMap))
            ->first(fn ($role) => $user->hasRole($role));

        if ($role === null) {
            return;
        }

        $this->sendNotification->execute(
            userId: $user->id,
            type: 'welcome',
            title: __('notifications.welcome_to_dashboard.title'),
            message: __($this->roleWelcomeMap[$role]),
            link: route('user.dashboard'),
        );

        $user->update(['first_login_at' => now()]);
    }
}
