<?php

declare(strict_types=1);

namespace App\Assignment\Listeners;

use App\Assignment\Events\AssignmentPublished;
use App\Core\Contracts\SendsNotifications;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyOnAssignmentPublished implements ShouldQueue
{
    public function __construct(
        protected SendsNotifications $sendNotification,
    ) {}

    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment;

        $this->sendNotification->execute(
            userId: $assignment->created_by,
            type: 'assignment_published',
            title: __('notifications.assignment_published.title'),
            message: __('notifications.assignment_published.message', ['name' => $assignment->title]),
            link: route('assignment.show', $assignment),
        );
    }
}
