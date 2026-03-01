<?php

declare(strict_types=1);

namespace Modules\Setup\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when the application setup process is successfully finalized.
 * [SYRS-F-101] State Machine Completion.
 */
class SetupFinalized
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $schoolName,
        public readonly string $installedAt,
    ) {}
}
