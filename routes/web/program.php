<?php

declare(strict_types=1);

use App\Modules\Program\Domain\Internship\Livewire\InternshipManager;
use App\Modules\Program\Domain\InternshipGroup\Livewire\InternshipGroupManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships', InternshipManager::class)->name('internships');
        Route::livewire('/internships/groups', InternshipGroupManager::class)->name(
            'internships.groups',
        );
    });
