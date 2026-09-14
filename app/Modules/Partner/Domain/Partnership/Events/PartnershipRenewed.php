<?php

declare(strict_types=1);

namespace App\Modules\Partner\Domain\Partnership\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;

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
