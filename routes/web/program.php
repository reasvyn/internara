<?php

declare(strict_types=1);

use App\Domain\Program\Aggregates\InternshipGroup\Livewire\InternshipGroupManager;
use App\Domain\Program\Aggregates\Internship\Livewire\InternshipManager;
use App\Domain\Program\Aggregates\InternshipPhase\Livewire\InternshipPhaseManager;
use App\Domain\Program\Aggregates\DocumentRequirement\Livewire\RequirementManager;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships', InternshipManager::class)->name('internships');
        Route::livewire('/internships/{internship}/requirements', RequirementManager::class)->name('internships.requirements');
        Route::livewire('/internships/groups', InternshipGroupManager::class)->name('internships.groups');
        Route::livewire('/internships/phases', InternshipPhaseManager::class)->name('internships.phases');
    });
