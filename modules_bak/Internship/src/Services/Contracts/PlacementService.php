<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Illuminate\Support\Collection;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<InternshipRegistration>
 */
interface PlacementService extends EloquentQuery
{
    /**
     * Determines if a specific student registration satisfies the criteria
     * required for industrial placement matching.
     *
     * Acts as a technical gate to ensure students have cleared mandatory
     * institutional prerequisites before being presented to industry partners.
     */
    public function isEligibleForPlacement(string $registrationId): bool;

    /**
     * Retrieves a collection of registrations authorized for bulk placement
     * within a specific academic cycle.
     *
     * Facilitates mass-orchestration of internship assignments, ensuring
     * that administrative workflows remain efficient during peak periods.
     *
     * @return Collection<int, InternshipRegistration>
     */
    public function getEligibleRegistrations(string $academicYear): Collection;

    /**
     * Executes an atomic batch matching operation for multiple registrations.
     *
     * Implements high-volume pairings between students and industry slots,
     * returning the total count of successfully established assignments.
     *
     * @param array<string, string> $pairings Map of registration UUIDs to placement UUIDs.
     */
    public function bulkMatch(array $pairings): int;

    /**
     * Assigns a specific industry slot to a student with forensic audit logging.
     *
     * Formalizes the relationship between the student and the industry
     * partner, capturing optional administrative justification for the assignment.
     */
    public function assignPlacement(
        string $registrationId,
        string $placementId,
        ?string $reason = null,
    ): bool;

    /**
     * Transitions a student to a new industrial slot while preserving the
     * historical audit trail.
     *
     * Used for corrective realignment of placements (e.g., student relocation
     * or partner request), requiring a mandatory justification for transparency.
     */
    public function changePlacement(
        string $registrationId,
        string $newPlacementId,
        string $reason,
    ): bool;
}
