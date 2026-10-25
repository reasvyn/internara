<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Listeners;

use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Enrollment\Domain\Registration\Events\RegistrationVerified;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyStudentOnRegistrationVerified implements ShouldQueue
{
    public function __construct(
        protected SendsNotifications $sendNotification,
    ) {}

    public function handle(RegistrationVerified $event): void
    {
        $registration = $event->registration;

        $this->sendNotification->execute(new NotificationData(
            userId: $registration->student_id,
            type: 'registration_verified',
            title: __('notifications.registration_verified.title'),
            message: __('notifications.registration_verified.message', [
                'internship' => $registration->internship?->name ?? '',
            ]),
            data: [
                'registration_id' => $registration->id,
                'placement_id' => $registration->placement_id,
            ],
            link: '/student/dashboard',
        ));
    }
}
