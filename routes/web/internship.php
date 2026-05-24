<?php

declare(strict_types=1);

use App\Domain\Internship\Livewire\BriefingManager;
use App\Domain\Internship\Livewire\InternshipGroupManager;
use App\Domain\Internship\Livewire\InternshipManager;
use App\Domain\Internship\Livewire\InternshipPhaseManager;
use App\Domain\Internship\Livewire\RequirementManager;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships', InternshipManager::class)->name('internships');
        Route::livewire('/internships/briefings', BriefingManager::class)->name('internships.briefings');
        Route::livewire('/internships/{internship}/requirements', RequirementManager::class)->name('internships.requirements');
        Route::livewire('/internships/groups', InternshipGroupManager::class)->name('internships.groups');
        Route::livewire('/internships/phases', InternshipPhaseManager::class)->name('internships.phases');
    });
