<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Events;

use App\Partners\Partnership\Models\Partnership;

final readonly class PartnershipCreated
{
    public function __construct(
        public Partnership $partnership,
    ) {}
}