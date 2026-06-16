<?php

declare(strict_types=1);

namespace App\Guidance\Handbook\Events;

use App\Core\Events\BaseEvent;
use App\Document\Models\Document;

final class HandbookUpdated extends BaseEvent
{
    public function __construct(public Document $handbook) {}

    public function eventName(): string
    {
        return 'handbook.updated';
    }
}
