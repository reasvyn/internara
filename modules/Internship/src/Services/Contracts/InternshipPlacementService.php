<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\Internship\Models\InternshipPlacement
 *
 * @extends EloquentQuery<TModel>
 */
interface InternshipPlacementService extends EloquentQuery
{
    /**
     * Get the number of available slots for a placement.
     */
    public function getAvailableSlots(string $placementId): int;

    /**
     * Check if a placement has available slots.
     */
    public function hasAvailableSlots(string $placementId): bool;
}
