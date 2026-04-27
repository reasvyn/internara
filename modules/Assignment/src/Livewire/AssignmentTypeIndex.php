<?php

declare(strict_types=1);

namespace Modules\Assignment\Livewire;

use Livewire\Attributes\Computed;
use Modules\Assignment\Services\Contracts\AssignmentTypeService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class AssignmentTypeIndex
 *
 * The main entry point for internship assignment category management.
 */
class AssignmentTypeIndex extends RecordIndex
{
    /**
     * The assignment type service instance.
     */
    protected AssignmentTypeService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'assignment::assignment-type-manager';

    protected string $titleKey = 'assignment::ui.menu.assignment_types';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(AssignmentTypeService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for assignment types.
     */
    #[Computed]
    public function stats(): array
    {
        return [
            [
                'title' => __('assignment::ui.stats.total_types'),
                'value' => $this->service->count(),
                'icon' => 'tabler.category',
                'variant' => 'metadata',
            ],
        ];
    }
}
