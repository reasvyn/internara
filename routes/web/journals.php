<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\AbsenceRequest\Livewire\AbsenceRequestForm;
use App\Modules\Journals\Domain\Attendance\Livewire\AttendanceManager;
use App\Modules\Journals\Domain\Attendance\Livewire\StudentClockIn;
use App\Modules\Journals\Domain\Logbook\Livewire\LogbookEntry;
use App\Modules\Journals\Domain\Logbook\Livewire\LogbookManager;
use App\Modules\Journals\Domain\MonitoringVisit\Livewire\StudentVisitList;
use App\Modules\Journals\Domain\MonitoringVisit\Livewire\VisitManager;
use App\Modules\Journals\Domain\SupervisionLog\Livewire\StudentLogManager;
use App\Modules\Journals\Domain\SupervisionLog\Livewire\SupervisorReviewManager;
use App\Modules\Journals\Http\Controllers\LogbookReportController;

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/logbook', LogbookEntry::class)->name('logbook');
        Route::livewire('/attendance', StudentClockIn::class)->name('attendance');
        Route::livewire('/attendance/absence', AbsenceRequestForm::class)->name(
            'attendance.absence',
        );
        Route::get('/supervision-logs', StudentLogManager::class)->name('supervision-logs');
        Route::get('/monitoring-visits', StudentVisitList::class)->name('monitoring-visits');
    });

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/attendance', AttendanceManager::class)->name('attendance');
    });

Route::livewire('/admin/logbook', LogbookManager::class)
    ->name('sysadmin.logbook')
    ->middleware(['auth', 'role:super_admin|admin|teacher|supervisor']);

Route::get('/admin/logbook/report/{registration}', LogbookReportController::class)
    ->name('sysadmin.logbook.report')
    ->middleware(['auth', 'role:super_admin|admin|teacher|supervisor']);

// Students may nominate either a teacher or a supervisor as the mentor of a
// supervision log, so both roles need the review screen their sidebar links to.
Route::prefix('supervision')
    ->name('supervision.')
    ->middleware(['auth', 'role:teacher|supervisor'])
    ->group(function () {
        Route::get('/logs', SupervisorReviewManager::class)->name('logs');
    });

Route::prefix('monitoring-visits')
    ->name('monitoring-visits.')
    ->middleware(['auth', 'role:teacher|super_admin|admin'])
    ->group(function () {
        Route::get('/', VisitManager::class)->name('index');
    });
