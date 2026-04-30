<?php

declare(strict_types=1);

namespace Modules\Assignment\Livewire;

use Livewire\Attributes\Computed;
use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class AssignmentIndex
 *
 * The main entry point for internship assignment management.
 */
class AssignmentIndex extends RecordIndex
{
    /**
     * The assignment service instance.
     */
    protected AssignmentService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'assignment::assignment-manager';

    protected string $titleKey = 'assignment::ui.manage_assignments';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(AssignmentService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for assignments.
     */
    #[Computed]
    public function stats(): array
    {
        return [
            [
                'title' => __('assignment::ui.stats.total_assignments'),
                'value' => $this->service->count(),
                'icon' => 'tabler.checklist',
                'variant' => 'metadata',
            ],
            [
                'title' => __('assignment::ui.stats.mandatory'),
                'value' => $this->service->count(['is_mandatory' => true]),
                'icon' => 'tabler.exclamation-circle',
                'variant' => 'warning',
            ],
            [
                'title' => __('assignment::ui.stats.upcoming'),
                'value' => $this->service->query()->where('due_date', '>=', now())->count(),
                'icon' => 'tabler.clock',
                'variant' => 'info',
            ],
        ];
    }
}
