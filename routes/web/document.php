<?php

declare(strict_types=1);

use App\Document\OfficialDocument\Http\Controllers\DocumentRenderController;
use App\Document\OfficialDocument\Livewire\ReportsManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/reports', ReportsManager::class)->name('reports.index');

        Route::get('/documents/{document}/render/{registration}', [DocumentRenderController::class, 'show'])->name('documents.render');
        Route::get('/documents/{document}/render/{registration}/save', [DocumentRenderController::class, 'store'])->name('documents.render.store');
    });
