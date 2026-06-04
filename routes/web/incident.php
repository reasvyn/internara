<?php

declare(strict_types=1);

use App\Domain\Incident\Aggregates\IncidentReport\Livewire\IncidentForm;
use App\Domain\Incident\Aggregates\IncidentReport\Livewire\IncidentManager;

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/incidents/report', IncidentForm::class)->name('incidents.report');
    });

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/incidents', IncidentManager::class)->name('incidents');
    });
