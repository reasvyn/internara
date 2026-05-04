<?php

declare(strict_types=1);

namespace App\Domain\Setup\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event triggered when the application setup wizard is successfully completed.
 *
 * S3 - Scalable: Allows other domains to hook into the post-installation process.
 */
class SetupFinalized
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ?string $schoolName = null,
        public ?string $installedAt = null
    ) {}
}
