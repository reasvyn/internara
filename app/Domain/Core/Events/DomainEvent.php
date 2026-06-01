<?php

declare(strict_types=1);

namespace App\Domain\Core\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class DomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
}
