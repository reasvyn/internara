<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Services\Contracts\InternshipPlacementService as Contract;
use Modules\Shared\Services\EloquentQuery;

class InternshipPlacementService extends EloquentQuery implements Contract
{
    public function __construct(InternshipPlacement $model)
    {
        $this->setModel($model);
        $this->setBaseQuery($model->newQuery()->with('internship'));
        $this->setSearchable(['company_name', 'contact_person']);
        $this->setSortable(['company_name', 'capacity_quota', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableSlots(string $placementId): int
    {
        $placement = $this->find($placementId);

        if (! $placement) {
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
