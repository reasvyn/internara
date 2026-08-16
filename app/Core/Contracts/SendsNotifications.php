<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\Core\Channels\Data\NotificationData;

interface SendsNotifications
{
    public function execute(NotificationData $data): mixed;
}
