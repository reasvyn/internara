<?php

declare(strict_types=1);

namespace Modules\Internship\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a student is successfully registered for an internship program.
 *
 * Mandate: Must only carry the UUID to ensure lightweight cross-module signaling.
 */
class InternshipRegistered
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $registrationId) {}
}
