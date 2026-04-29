<?php

declare(strict_types=1);

namespace Modules\Student\Livewire;

use Livewire\Attributes\Computed;
use Modules\Student\Services\Contracts\StudentService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class StudentIndex
 *
 * The main entry point for student management.
 */
class StudentIndex extends RecordIndex
{
    /**
     * The student service instance.
     */
    protected StudentService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'student::student-manager';

    protected string $titleKey = 'student::ui.management_title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(StudentService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for student distribution.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('student::ui.stats.total'),
                'value' => $raw['total'],
                'icon' => 'tabler.school',
                'variant' => 'metadata',
            ],
            [
                'title' => __('student::ui.stats.verified'),
                'value' => $raw['verified'],
                'icon' => 'tabler.certificate-check',
                'variant' => 'info',
            ],
            [
                'title' => __('student::ui.stats.active'),
                'value' => $raw['active'],
                'icon' => 'tabler.activity',
                'variant' => 'success',
            ],
            [
                'title' => __('student::ui.stats.pending'),
                'value' => $raw['pending'],
                'icon' => 'tabler.clock-pause',
                'variant' => 'warning',
            ],
        ];
    }
}
