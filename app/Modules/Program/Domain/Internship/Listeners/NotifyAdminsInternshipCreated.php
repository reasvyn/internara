<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\Internship\Listeners;

use App\Modules\Program\Domain\Internship\Events\InternshipCreated;
use App\Modules\Program\Domain\Internship\Notifications\InternshipCreatedNotification;
use App\Modules\User\Models\User;
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

        Notification::send(
            $admins,
            new InternshipCreatedNotification(
                internshipName: $event->internship->name,
                createdByName: $event->createdBy?->name,
            ),
        );
    }
}
