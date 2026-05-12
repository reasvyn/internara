<?php

declare(strict_types=1);

namespace App\Listeners\Internship;

use App\Events\Internship\InternshipCreated;
use App\Models\User;
use App\Notifications\Internship\InternshipCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class NotifyAdminsInternshipCreated implements ShouldQueue
{
    public function handle(InternshipCreated $event): void
    {
        try {
            $admins = User::role(['super_admin', 'admin'])->get();
        } catch (RoleDoesNotExist) {
            return;
        }

        Notification::send($admins, new InternshipCreatedNotification(
            internshipName: $event->internship->name,
            createdByName: $event->createdBy?->name,
        ));
    }
}
