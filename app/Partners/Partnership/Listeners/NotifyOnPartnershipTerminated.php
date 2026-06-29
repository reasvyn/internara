<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Listeners;

use App\Core\Contracts\SendsNotifications;
use App\Partners\Partnership\Events\PartnershipTerminated;
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

        $this->sendNotification->execute(
            userId: $userId,
            type: 'partnership_terminated',
            title: __('notifications.partnership_terminated.title'),
            message: __('notifications.partnership_terminated.message', [
                'company' => $partnership->company?->name,
            ]),
        );
    }
}
