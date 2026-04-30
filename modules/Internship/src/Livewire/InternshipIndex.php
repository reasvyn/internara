<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Attributes\Computed;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class InternshipIndex
 *
 * The main entry point for internship program management, displaying
 * executive statistics and the record management interface.
 */
class InternshipIndex extends RecordIndex
{
    /**
     * The internship service instance.
     */
    protected InternshipService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'internship::internship-manager';

    protected string $titleKey = 'internship::ui.program_title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(InternshipService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get institutional summary metrics for internship programs.
     * Maps raw service stats into the standardized UI format.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('internship::ui.stats.total_programs'),
                'value' => $raw['total'],
                'icon' => 'tabler.layers-intersect',
                'variant' => 'metadata',
            ],
            [
                'title' => __('internship::ui.stats.open_registration'),
                'value' => $raw['active'],
                'icon' => 'tabler.door-open',
                'variant' => 'success',
            ],
            [
                'title' => __('internship::ui.stats.ongoing_programs'),
                'value' => $raw['ongoing'],
                'icon' => 'tabler.activity',
                'variant' => 'primary',
            ],
            [
                'title' => __('internship::ui.stats.upcoming_programs'),
                'value' => $raw['upcoming'],
                'icon' => 'tabler.calendar-bolt',
                'variant' => 'info',
            ],
        ];
    }
}
