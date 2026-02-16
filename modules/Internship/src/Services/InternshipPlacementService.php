<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Services\Contracts\InternshipPlacementService as Contract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Implements the business logic for managing internship placements.
 */
class InternshipPlacementService extends EloquentQuery implements Contract
{
    /**
     * Create a new service instance.
     */
    public function __construct(InternshipPlacement $model)
    {
        $this->setModel($model);
        $this->setBaseQuery($model->newQuery()->with(['internship', 'company']));
        $this->setSearchable(['company.name']);
        $this->setSortable(['capacity_quota', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableSlots(string $placementId): int
    {
        $placement = $this->find($placementId);

        if (!$placement) {
            return 0;
        }

        $occupiedSlots = $placement->registrations()->count();

        return max(0, $placement->capacity_quota - $occupiedSlots);
    }

    /**
     * {@inheritdoc}
     */
    public function hasAvailableSlots(string $placementId): bool
    {
        return $this->getAvailableSlots($placementId) > 0;
    }
}
