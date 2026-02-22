<?php

declare(strict_types=1);

namespace Modules\Teacher\Livewire;

use Illuminate\View\View;
use Modules\User\Livewire\UserManager;

/**
 * Class TeacherManager
 * 
 * Specialized manager for teacher users.
 */
class TeacherManager extends UserManager
{
    public string $targetRole = 'teacher';

    /**
     * Render the teacher manager view.
     */
    public function render(): View
    {
        $title = __('admin::ui.menu.teachers');

        return view('teacher::livewire.teacher-manager', [
            'title' => $title,
            'roleKey' => $this->targetRole,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
