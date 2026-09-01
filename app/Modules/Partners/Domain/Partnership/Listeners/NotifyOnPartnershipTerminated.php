<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Listeners;

use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipTerminated;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyOnPartnershipTerminated implements ShouldQueue
{
    public function __construct(
        protected SendsNotifications $sendNotification,
    ) {}

    public function handle(PartnershipTerminated $event): void
    {
        $partnership = $event->partnership;
        $userId = $partnership->company?->created_by;

        if ($userId === null) {
            return;
        }

        $this->sendNotification->execute(new NotificationData(
            userId: $userId,
            type: 'partnership_terminated',
            title: __('notifications.partnership_terminated.title'),
            message: __('notifications.partnership_terminated.message', [
                'company' => $partnership->company->name,
            ]),
        ));
    }
}
