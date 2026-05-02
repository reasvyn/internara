<?php

declare(strict_types=1);

namespace App\Events\Setup;

use App\Models\Setup;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event triggered when the application setup wizard is successfully completed.
 *
 * S3 - Scalable: Allows other modules to hook into the post-installation process.
 */
class SetupFinalized
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Setup $setup)
    {
    }
}
