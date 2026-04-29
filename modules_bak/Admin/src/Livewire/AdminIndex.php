<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Livewire\Attributes\Computed;
use Modules\Admin\Services\Contracts\AdminService;
use Modules\Permission\Enums\Role;
use Modules\UI\Livewire\RecordIndex;

/**
 * Class AdminIndex
 *
 * The main entry point for administrator management.
 */
class AdminIndex extends RecordIndex
{
    /**
     * The admin service instance.
     */
    protected AdminService $service;

    /**
     * Configuration for the base RecordIndex.
     */
    protected string $managerComponent = 'admin::admin-manager';

    protected string $titleKey = 'admin::ui.menu.administrators';

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(AdminService $service): void
    {
        $this->service = $service;
    }

    /**
     * Mount the component with security gate.
     */
    public function mount(): void
    {
        abort_unless(
            auth()->user()->hasRole(Role::SUPER_ADMIN->value),
            403,
            __('user::exceptions.super_admin_unauthorized'),
        );

        parent::mount();
    }

    /**
     * Get summary metrics for administrator distribution.
     */
    #[Computed]
    public function stats(): array
    {
        $raw = $this->service->getStats();

        return [
            [
                'title' => __('admin::ui.stats.total'),
                'value' => $raw['total'],
                'icon' => 'tabler.users',
                'variant' => 'metadata',
            ],
            [
                'title' => __('admin::ui.stats.admins'),
                'value' => $raw['admins'],
                'icon' => 'tabler.user-shield',
                'variant' => 'info',
            ],
            [
                'title' => __('admin::ui.stats.active'),
                'value' => $raw['active'],
                'icon' => 'tabler.circle-check',
                'variant' => 'success',
            ],
            [
                'title' => __('admin::ui.stats.pending'),
                'value' => $raw['pending'],
                'icon' => 'tabler.mail-forward',
                'variant' => 'warning',
            ],
        ];
    }
}
