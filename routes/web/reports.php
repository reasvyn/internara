<?php

declare(strict_types=1);

use App\Reports\Report\Http\Controllers\ReportController;
use App\Reports\Report\Livewire\ReportWriter;

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/reports', ReportWriter::class)->name('reports');
    });

Route::middleware(['auth'])
    ->group(function () {
        Route::get('/reports/{report}', function (App\Reports\Report\Models\Report $report) {
            return redirect()->route('student.reports');
        })->name('reports.show');
    });

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name(
            'reports.download',
        );
    });
