<?php

declare(strict_types=1);

namespace App\Auth\SuperAdmin\Listeners;

use App\Auth\SuperAdmin\Events\SuperAdminRecovered;
use App\Auth\SuperAdmin\Notifications\SuperAdminRecoveredNotification;
use App\User\Models\User;
use Illuminate\Support\Facades\Notification;

final class NotifySuperAdminsOfRecovery
{
    public function handle(SuperAdminRecovered $event): void
    {
        $existingAdmins = User::role('super_admin')
            ->where('id', '!=', $event->user->id)
            ->get();

        if ($existingAdmins->isEmpty()) {
            return;
        }

        Notification::send(
            $existingAdmins,
            new SuperAdminRecoveredNotification(
                recoveredEmail: $event->email,
            ),
        );
    }
}
