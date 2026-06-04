<?php

declare(strict_types=1);

use App\Domain\Document\Aggregates\OfficialDocument\Http\Controllers\DocumentRenderController;
use App\Domain\Document\Aggregates\OfficialDocument\Livewire\ReportsManager;
use App\Domain\Reports\Aggregates\Report\Http\Controllers\ReportController;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::livewire('/', ReportsManager::class)->name('index');
            Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
        });

        Route::get('/documents/{document}/render/{registration}', [DocumentRenderController::class, 'show'])->name('documents.render');
        Route::get('/documents/{document}/render/{registration}/save', [DocumentRenderController::class, 'store'])->name('documents.render.store');
    });
