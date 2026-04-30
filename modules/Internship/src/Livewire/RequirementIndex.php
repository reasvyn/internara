<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Attributes\Computed;
use Modules\Internship\Services\Contracts\InternshipRequirementService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class RequirementIndex
 *
 * The main entry point for internship requirement management.
 */
class RequirementIndex extends RecordIndex
{
    /**
     * The requirement service instance.
     */
    protected InternshipRequirementService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'internship::requirement-manager';

    protected string $titleKey = 'internship::ui.requirement_title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(InternshipRequirementService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for internship requirements.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('internship::ui.stats.total_requirements'),
                'value' => $raw['total'],
                'icon' => 'tabler.list-check',
                'variant' => 'metadata',
            ],
            [
                'title' => __('internship::ui.stats.mandatory_requirements'),
                'value' => $raw['mandatory'],
                'icon' => 'tabler.exclamation-circle',
                'variant' => 'error',
            ],
            [
                'title' => __('internship::ui.stats.active_requirements'),
                'value' => $raw['active'],
                'icon' => 'tabler.circle-check',
                'variant' => 'success',
            ],
            [
                'title' => __('internship::ui.stats.document_requirements'),
                'value' => $raw['documents'],
                'icon' => 'tabler.file-description',
                'variant' => 'info',
            ],
        ];
    }
}
