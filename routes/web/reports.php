<?php

declare(strict_types=1);

use App\Modules\Reports\Domain\StudentReport\Http\Controllers\StudentReportController;
use App\Modules\Reports\Domain\StudentReport\Livewire\StudentReportsManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/student-reports', StudentReportsManager::class)->name(
            'student-reports.index',
        );
        Route::get('/student-reports/{report}/download', [
            StudentReportController::class,
            'download',
        ])->name('student-reports.download');
    });
