<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Events;

use App\Core\Events\BaseEvent;
use App\Partners\Partnership\Models\Partnership;

final class PartnershipRenewed extends BaseEvent
{
    public function __construct(
        public Partnership $newPartnership,
        public Partnership $oldPartnership,
    ) {}

    public function eventName(): string
    {
        return 'partnership.renewed';
    }
}
