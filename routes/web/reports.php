<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\Report\Http\Controllers\ReportController;
use App\Modules\Reports\Domain\Report\Livewire\ReportsManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/reports', ReportsManager::class)->name('reports.index');
        Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name(
            'reports.download',
        );
    });
