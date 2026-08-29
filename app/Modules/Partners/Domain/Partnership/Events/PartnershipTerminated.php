<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;

final class PartnershipTerminated extends BaseEvent
{
    public function __construct(public Partnership $partnership) {}

    public function eventName(): string
    {
        return 'partnership.terminated';
    }
}
