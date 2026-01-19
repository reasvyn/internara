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
     * Determine if a student registration is eligible for placement matching.
     */
    public function isEligibleForPlacement(string $registrationId): bool;

    /**
     * Get a list of registrations that are ready for bulk placement.
     *
     * @return Collection<int, InternshipRegistration>
     */
    public function getEligibleRegistrations(string $academicYear): Collection;

    /**
     * Perform bulk matching for a set of registrations.
     *
     * @param array<string, string> $pairings Array of registration_id => placement_id
     */
    public function bulkMatch(array $pairings): int;
}
