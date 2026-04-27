<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Attributes\Computed;
use Modules\Internship\Services\Contracts\CompanyService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class CompanyIndex
 *
 * The main entry point for industry partner management.
 */
class CompanyIndex extends RecordIndex
{
    /**
     * The company service instance.
     */
    protected CompanyService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'internship::company-manager';

    protected string $titleKey = 'internship::ui.company.title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(CompanyService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for industry partners.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('internship::ui.company.stats.total'),
                'value' => $raw['total'],
                'icon' => 'tabler.building-skyscraper',
                'variant' => 'metadata',
            ],
            [
                'title' => __('internship::ui.company.stats.active'),
                'value' => $raw['active_partners'],
                'icon' => 'tabler.circle-check',
                'variant' => 'success',
            ],
            [
                'title' => __('internship::ui.company.stats.with_mentors'),
                'value' => $raw['with_mentors'],
                'icon' => 'tabler.users',
                'variant' => 'info',
            ],
        ];
    }
}
