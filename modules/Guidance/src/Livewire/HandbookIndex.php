<?php

declare(strict_types=1);

namespace Modules\Guidance\Livewire;

use Livewire\Attributes\Computed;
use Modules\Guidance\Services\Contracts\HandbookService;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class HandbookIndex
 *
 * The main entry point for instructional handbook management.
 */
class HandbookIndex extends RecordIndex
{
    /**
     * The handbook service instance.
     */
    protected HandbookService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'guidance::manage-handbook';

    protected string $titleKey = 'guidance::ui.manage_title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(HandbookService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for handbooks.
     */
    #[Computed]
    public function stats(): array
    {
        $total = $this->service->count();
        $mandatory = $this->service->count(['is_mandatory' => true]);
        $active = $this->service->count(['is_active' => true]);

        return [
            [
                'title' => __('guidance::ui.stats.total_handbooks'),
                'value' => $total,
                'icon' => 'tabler.books',
                'variant' => 'metadata',
            ],
            [
                'title' => __('guidance::ui.stats.mandatory'),
                'value' => $mandatory,
                'icon' => 'tabler.exclamation-circle',
                'variant' => 'warning',
            ],
            [
                'title' => __('guidance::ui.stats.active'),
                'value' => $active,
                'icon' => 'tabler.circle-check',
                'variant' => 'success',
            ],
        ];
    }
}
