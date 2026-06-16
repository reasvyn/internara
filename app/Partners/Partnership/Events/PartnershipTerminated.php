<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Events;

use App\Core\Events\BaseEvent;
use App\Partners\Partnership\Models\Partnership;

final class PartnershipTerminated extends BaseEvent
{
    public function __construct(public Partnership $partnership) {}

    public function eventName(): string
    {
        return 'partnership.terminated';
    }
}
