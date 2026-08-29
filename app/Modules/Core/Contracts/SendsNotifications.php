<?php

declare(strict_types=1);

namespace App\Modules\Core\Contracts;

use App\Modules\Core\Channels\Data\NotificationData;

interface SendsNotifications
{
    public function execute(NotificationData $data): mixed;
}
