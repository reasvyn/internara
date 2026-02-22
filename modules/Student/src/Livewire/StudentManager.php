<?php

declare(strict_types=1);

namespace Modules\Student\Livewire;

use Illuminate\View\View;
use Modules\User\Livewire\UserManager;

/**
 * Class StudentManager
 * 
 * Specialized manager for student users.
 */
class StudentManager extends UserManager
{
    public string $targetRole = 'student';

    /**
     * Render the student manager view.
     */
    public function render(): View
    {
        $title = __('admin::ui.menu.students');

        return view('student::livewire.student-manager', [
            'title' => $title,
            'roleKey' => $this->targetRole,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
