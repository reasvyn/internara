<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Models\PlacementHistory;

/**
 * Interface for logging placement lifecycle events.
 */
interface PlacementLogger
{
    /**
     * Log a placement event for a registration.
     *
     * @param string $action The action performed (e.g. 'assigned', 'changed', 'completed')
     * @param string|null $reason Optional reason for the action
     * @param array<string, mixed> $metadata Optional structured data
     * @param string|null $placementId Optional specific placement ID to log
     */
    public function log(
        InternshipRegistration $registration,
        string $action,
        ?string $reason = null,
        array $metadata = [],
        ?string $placementId = null,
    ): PlacementHistory;

    /**
     * Log an initial placement assignment.
     */
    public function logAssignment(
        InternshipRegistration $registration,
        ?string $reason = null,
    ): PlacementHistory;

    /**
     * Log a placement change.
     */
    public function logChange(
        InternshipRegistration $registration,
        string $oldPlacementId,
        string $newPlacementId,
        ?string $reason = null,
    ): PlacementHistory;
}
