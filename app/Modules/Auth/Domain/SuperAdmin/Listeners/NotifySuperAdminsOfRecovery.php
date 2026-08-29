<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\SuperAdmin\Listeners;

use App\Modules\Auth\Domain\SuperAdmin\Events\SuperAdminRecovered;
use App\Modules\Auth\Domain\SuperAdmin\Notifications\SuperAdminRecoveredNotification;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

final class NotifySuperAdminsOfRecovery implements ShouldQueue
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
