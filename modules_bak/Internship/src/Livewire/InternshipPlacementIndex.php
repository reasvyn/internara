<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Attributes\Computed;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class InternshipPlacementIndex
 *
 * The main entry point for internship placement management.
 */
class InternshipPlacementIndex extends RecordIndex
{
    /**
     * The placement service instance.
     */
    protected InternshipPlacementService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'internship::internship-placement-manager';

    protected string $titleKey = 'internship::ui.placement_title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(InternshipPlacementService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for internship placements.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('internship::ui.stats.total_locations'),
                'value' => $raw['total_locations'],
                'icon' => 'tabler.map-pins',
                'variant' => 'metadata',
            ],
            [
                'title' => __('internship::ui.stats.total_quota'),
                'value' => $raw['total_quota'],
                'icon' => 'tabler.users',
                'variant' => 'info',
            ],
            [
                'title' => __('internship::ui.stats.filled_quota'),
                'value' => $raw['filled_quota'],
                'icon' => 'tabler.user-check',
                'variant' => 'success',
            ],
            [
                'title' => __('internship::ui.stats.utilization_rate'),
                'value' => $raw['utilization_rate'] . '%',
                'icon' => 'tabler.chart-pie',
                'variant' => 'primary',
            ],
        ];
    }
}
