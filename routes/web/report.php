<?php

declare(strict_types=1);

use App\Modules\Report\Domain\StudentReport\Http\Controllers\StudentReportController;
use App\Modules\Report\Domain\StudentReport\Livewire\StudentReportsManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/student-report', StudentReportsManager::class)->name(
            'student-report.index',
        );
        Route::get('/student-report/{report}/download', [
            StudentReportController::class,
            'download',
        ])->name('student-report.download');
    });
