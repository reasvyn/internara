<?php

declare(strict_types=1);

namespace Modules\Teacher\Livewire;

use Livewire\Attributes\Computed;
use Modules\Teacher\Services\Contracts\TeacherService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class TeacherIndex
 *
 * The main entry point for academic teacher management.
 */
class TeacherIndex extends RecordIndex
{
    /**
     * The teacher service instance.
     */
    protected TeacherService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'teacher::teacher-manager';

    protected string $titleKey = 'teacher::ui.management_title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(TeacherService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for teacher distribution.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('teacher::ui.stats.total'),
                'value' => $raw['total'],
                'icon' => 'tabler.user-bolt',
                'variant' => 'metadata',
            ],
            [
                'title' => __('teacher::ui.stats.active'),
                'value' => $raw['active'],
                'icon' => 'tabler.circle-check',
                'variant' => 'success',
            ],
            [
                'title' => __('teacher::ui.stats.pending'),
                'value' => $raw['pending'],
                'icon' => 'tabler.clock-pause',
                'variant' => 'warning',
            ],
        ];
    }
}
