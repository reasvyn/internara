<?php

declare(strict_types=1);

use App\Program\DocumentRequirement\Livewire\RequirementManager;
use App\Program\Internship\Livewire\InternshipManager;
use App\Program\InternshipGroup\Livewire\InternshipGroupManager;
use App\Program\InternshipPhase\Livewire\InternshipPhaseManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships', InternshipManager::class)->name('internships');
        Route::livewire('/internships/{internship}/requirements', RequirementManager::class)->name('internships.requirements');
        Route::livewire('/internships/groups', InternshipGroupManager::class)->name('internships.groups');
        Route::livewire('/internships/phases', InternshipPhaseManager::class)->name('internships.phases');
    });
