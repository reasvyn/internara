<?php

declare(strict_types=1);

namespace Modules\Assignment\Services\Contracts;

use Modules\Assignment\Models\Assignment;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<Assignment>
 */
interface AssignmentService extends EloquentQuery
{
    /**
     * Create default assignments for a specific internship program or academic year.
     */
    public function createDefaults(string $internshipId, ?string $academicYear = null): void;

    /**
     * Verify if all mandatory assignments for a registration are completed.
     */
    public function isFulfillmentComplete(string $registrationId, ?string $group = null): bool;

    /**
     * Get all available assignment types.
     */
    public function getTypes(): \Illuminate\Support\Collection;
}
