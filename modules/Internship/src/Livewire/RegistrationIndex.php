<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Attributes\Computed;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class RegistrationIndex
 *
 * The main entry point for internship registration management.
 */
class RegistrationIndex extends RecordIndex
{
    /**
     * The registration service instance.
     */
    protected RegistrationService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'internship::internship-registration';

    protected string $titleKey = 'internship::ui.registration_title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(RegistrationService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for internship registrations.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('internship::ui.stats.total_registrations'),
                'value' => $raw['total'],
                'icon' => 'tabler.file-pencil',
                'variant' => 'metadata',
            ],
            [
                'title' => __('internship::ui.stats.placed_students'),
                'value' => $raw['placed'],
                'icon' => 'tabler.map-pin-check',
                'variant' => 'success',
            ],
            [
                'title' => __('internship::ui.stats.unplaced_students'),
                'value' => $raw['unplaced'],
                'icon' => 'tabler.map-pin-off',
                'variant' => 'warning',
            ],
            [
                'title' => __('internship::ui.stats.new_registrations'),
                'value' => $raw['new'],
                'icon' => 'tabler.news',
                'variant' => 'info',
            ],
        ];
    }
}
