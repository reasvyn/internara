<?php

declare(strict_types=1);

namespace Modules\School\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a school record is deleted.
 *
 * This event allows other modules (e.g., Department) to perform
 * necessary cleanup without physical foreign keys.
 */
class SchoolDeleted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $schoolId) {}
}
