<?php

declare(strict_types=1);

namespace App\Program\Internship\Events;

use App\Core\Events\BaseEvent;

final class InternshipStatusBatchUpdated extends BaseEvent
{
    public function __construct(
        public int $count,
        public string $newStatus,
        public ?string $previousStatus = null,
    ) {}

    public function eventName(): string
    {
        return 'internship.status_batch_updated';
    }
}
