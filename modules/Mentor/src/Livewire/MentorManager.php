<?php

declare(strict_types=1);

namespace Modules\Mentor\Livewire;

use Illuminate\View\View;
use Modules\User\Livewire\UserManager;

/**
 * Class MentorManager
 * 
 * Specialized manager for industry mentor users.
 */
class MentorManager extends UserManager
{
    public string $targetRole = 'mentor';

    /**
     * Render the mentor manager view.
     */
    public function render(): View
    {
        $title = __('admin::ui.menu.mentors');

        return view('mentor::livewire.mentor-manager', [
            'title' => $title,
            'roleKey' => $this->targetRole,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
