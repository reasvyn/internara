<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Models\PlacementHistory;
use Modules\Internship\Services\Contracts\PlacementLogger as Contract;

/**
 * Service for logging placement lifecycle events.
 */
class PlacementLoggerService implements Contract
{
    /**
     * {@inheritdoc}
     */
    public function log(
        InternshipRegistration $registration,
        string $action,
        ?string $reason = null,
        array $metadata = [],
        ?string $placementId = null
    ): PlacementHistory {
        return PlacementHistory::create([
            'registration_id' => $registration->id,
            'placement_id' => $placementId ?: $registration->placement_id,
            'action' => $action,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function logAssignment(
        InternshipRegistration $registration,
        ?string $reason = null
    ): PlacementHistory {
        return $this->log(
            $registration,
            'assigned',
            $reason ?: 'Initial placement assignment'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function logChange(
        InternshipRegistration $registration,
        string $oldPlacementId,
        string $newPlacementId,
        ?string $reason = null
    ): PlacementHistory {
        return $this->log(
            $registration,
            'changed',
            $reason,
            [
                'old_placement_id' => $oldPlacementId,
                'new_placement_id' => $newPlacementId,
            ],
            $newPlacementId
        );
    }
}
