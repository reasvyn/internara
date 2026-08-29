<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Document\Models\Document;

final class HandbookUpdated extends BaseEvent
{
    public function __construct(public Document $handbook) {}

    public function eventName(): string
    {
        return 'handbook.updated';
    }
}
