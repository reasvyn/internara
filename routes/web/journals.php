<?php

declare(strict_types=1);

use App\Journals\AbsenceRequest\Livewire\AbsenceRequestForm;
use App\Journals\Attendance\Livewire\AttendanceManager;
use App\Journals\Attendance\Livewire\StudentClockIn;
use App\Journals\Http\Controllers\LogbookReportController;
use App\Journals\Logbook\Livewire\LogbookEntry;
use App\Journals\Logbook\Livewire\LogbookManager;
use App\Journals\MonitoringVisit\Livewire\StudentVisitList;
use App\Journals\MonitoringVisit\Livewire\VisitManager;
use App\Journals\SupervisionLog\Livewire\StudentLogManager;
use App\Journals\SupervisionLog\Livewire\SupervisorReviewManager;

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

Route::prefix('supervision')
    ->name('supervision.')
    ->middleware(['auth', 'role:supervisor'])
    ->group(function () {
        Route::get('/logs', SupervisorReviewManager::class)->name('logs');
    });

Route::prefix('monitoring-visits')
    ->name('monitoring-visits.')
    ->middleware(['auth', 'role:teacher|super_admin|admin'])
    ->group(function () {
        Route::get('/', VisitManager::class)->name('index');
    });
