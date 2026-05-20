<?php

declare(strict_types=1);

namespace App\Domain\Internship\Listeners;

use App\Domain\Internship\Events\InternshipCreated;
use App\Domain\Internship\Notifications\InternshipCreatedNotification;
use App\Domain\User\Models\User;
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
