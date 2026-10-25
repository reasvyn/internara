<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Listeners;

use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Enrollment\Domain\Registration\Events\RegistrationRejected;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyStudentOnRegistrationRejected implements ShouldQueue
{
    public function __construct(
        protected SendsNotifications $sendNotification,
    ) {}

    public function handle(RegistrationRejected $event): void
    {
        $registration = $event->registration;

        $this->sendNotification->execute(new NotificationData(
            userId: $registration->student_id,
            type: 'registration_rejected',
            title: __('notifications.registration_rejected.title'),
            message: __('notifications.registration_rejected.message', [
                'internship' => $registration->internship?->name ?? '',
                'reason' => $event->reason,
            ]),
            data: [
                'registration_id' => $registration->id,
                'reason' => $event->reason,
            ],
            link: '/student/dashboard',
        ));
    }
}
