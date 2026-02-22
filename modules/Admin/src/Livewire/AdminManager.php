<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\View\View;
use Modules\User\Livewire\UserManager;

/**
 * Class AdminManager
 * 
 * Specialized manager for system administrators.
 */
class AdminManager extends UserManager
{
    public string $targetRole = 'admin';

    /**
     * Render the admin manager view.
     */
    public function render(): View
    {
        $title = __('admin::ui.menu.administrators');

        return view('admin::livewire.admin-manager', [
            'title' => $title,
            'roleKey' => $this->targetRole,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
