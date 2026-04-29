<?php

declare(strict_types=1);

namespace Modules\Department\Livewire;

use Livewire\Attributes\Computed;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class DepartmentIndex
 *
 * The main entry point for department management.
 */
class DepartmentIndex extends RecordIndex
{
    /**
     * The department service instance.
     */
    protected DepartmentService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'department::department-manager';

    protected string $titleKey = 'department::ui.title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(DepartmentService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for departments.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('department::ui.stats.total'),
                'value' => $raw['total'],
                'icon' => 'tabler.building-community',
                'variant' => 'primary',
            ],
            [
                'title' => __('department::ui.stats.with_internships'),
                'value' => $raw['with_internships'],
                'icon' => 'tabler.briefcase',
                'variant' => 'success',
            ],
        ];
    }
}
