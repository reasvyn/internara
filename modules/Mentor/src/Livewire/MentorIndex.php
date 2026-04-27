<?php

declare(strict_types=1);

namespace Modules\Mentor\Livewire;

use Livewire\Attributes\Computed;
use Modules\Mentor\Services\Contracts\MentorService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class MentorIndex
 *
 * The main entry point for industry mentor management.
 */
class MentorIndex extends RecordIndex
{
    /**
     * The mentor service instance.
     */
    protected MentorService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'mentor::mentor-manager';

    protected string $titleKey = 'mentor::ui.management_title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(MentorService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for mentor distribution.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('mentor::ui.stats.total'),
                'value' => $raw['total'],
                'icon' => 'tabler.users',
                'variant' => 'metadata',
            ],
            [
                'title' => __('mentor::ui.stats.active'),
                'value' => $raw['active'],
                'icon' => 'tabler.circle-check',
                'variant' => 'success',
            ],
            [
                'title' => __('mentor::ui.stats.pending'),
                'value' => $raw['pending'],
                'icon' => 'tabler.clock-pause',
                'variant' => 'warning',
            ],
        ];
    }
}
