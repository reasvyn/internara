<?php

declare(strict_types=1);

use App\Domain\Incident\Aggregates\IncidentReport\Livewire\IncidentManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/incidents', IncidentManager::class)->name('incidents');
    });
