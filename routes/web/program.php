<?php

declare(strict_types=1);

use App\Program\Internship\Livewire\InternshipManager;
use App\Program\InternshipGroup\Livewire\InternshipGroupManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships', InternshipManager::class)->name('internships');
        Route::livewire('/internships/groups', InternshipGroupManager::class)->name(
            'internships.groups',
        );
    });
