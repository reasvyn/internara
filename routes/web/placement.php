<?php

declare(strict_types=1);

use App\Domain\Placement\Livewire\DirectPlacementManager;
use App\Domain\Placement\Livewire\PlacementChangeManager;
use App\Domain\Placement\Livewire\PlacementIndex;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships/placements', PlacementIndex::class)->name('internships.placements');
        Route::livewire('/internships/placements/direct', DirectPlacementManager::class)->name('internships.placements.direct');
        Route::livewire('/internships/placements/changes', PlacementChangeManager::class)->name('internships.placements.changes');
    });
