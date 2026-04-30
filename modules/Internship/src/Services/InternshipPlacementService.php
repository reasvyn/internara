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
        $this->setSearchable(['company.name', 'internship.title']);
        $this->setSortable(['capacity_quota', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        $totalQuota = (int) $this->model->newQuery()->sum('capacity_quota');
        $filledQuota = (int) $this->model
            ->newQuery()
            ->withCount('registrations')
            ->get()
            ->sum('registrations_count');

        return [
            'total_locations' => $this->count(),
            'total_quota' => $totalQuota,
            'filled_quota' => $filledQuota,
            'utilization_rate' =>
                $totalQuota > 0 ? (int) round(($filledQuota / $totalQuota) * 100) : 0,
        ];
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

        return $placement->remainingSlots;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAvailableSlots(string $placementId): bool
    {
        return $this->getAvailableSlots($placementId) > 0;
    }
}
