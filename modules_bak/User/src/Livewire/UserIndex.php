<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Livewire\Attributes\Computed;
use Modules\UI\Livewire\RecordIndex;
use Modules\User\Services\Contracts\UserService;

/**
 * Class UserIndex
 *
 * The main entry point for system user management.
 */
class UserIndex extends RecordIndex
{
    /**
     * The user service instance.
     */
    protected UserService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'user::user-manager';

    protected string $titleKey = 'user::ui.viewer.title';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(UserService $service): void
    {
        $this->service = $service;
    }

    /**
     * Get summary metrics for user distribution.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('user::ui.stats.total'),
                'value' => $raw['total'],
                'icon' => 'tabler.users',
                'variant' => 'metadata',
            ],
            [
                'title' => __('user::ui.stats.students'),
                'value' => $raw['students'],
                'icon' => 'tabler.school',
                'variant' => 'primary',
            ],
            [
                'title' => __('user::ui.stats.staff'),
                'value' => $raw['staff'],
                'icon' => 'tabler.user-bolt',
                'variant' => 'info',
            ],
            [
                'title' => __('user::ui.stats.active'),
                'value' => $raw['active'],
                'icon' => 'tabler.circle-check',
                'variant' => 'success',
            ],
        ];
    }
}
