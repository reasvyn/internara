<?php

declare(strict_types=1);

use App\Reports\Report\Http\Controllers\ReportController;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name(
            'reports.download',
        );
    });
