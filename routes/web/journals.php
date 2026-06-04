<?php

declare(strict_types=1);

use App\Domain\Enrollment\Models\Registration;
use App\Domain\Journals\Aggregates\AbsenceRequest\Livewire\AbsenceRequestForm;
use App\Domain\Journals\Aggregates\Attendance\Livewire\AttendanceManager;
use App\Domain\Journals\Aggregates\Attendance\Livewire\StudentClockIn;
use App\Domain\Journals\Aggregates\IndustryAssessment\Livewire\IndustryAssessmentForm;
use App\Domain\Journals\Aggregates\Logbook\Actions\CompileLogbookReportAction;
use App\Domain\Journals\Aggregates\Logbook\Livewire\LogbookEntry;
use App\Domain\Journals\Aggregates\Logbook\Livewire\LogbookManager;
use App\Domain\Journals\Aggregates\Schedule\Livewire\ScheduleIndex;

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/logbook', LogbookEntry::class)->name('logbook');
        Route::livewire('/attendance', StudentClockIn::class)->name('attendance');
        Route::livewire('/attendance/absence', AbsenceRequestForm::class)->name('attendance.absence');
    });

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/attendance', AttendanceManager::class)->name('attendance');
        Route::livewire('/schedules', ScheduleIndex::class)->name('schedules.index');
    });

Route::livewire('/admin/logbook', LogbookManager::class)
    ->name('sysadmin.logbook')
    ->middleware(['auth', 'role:super_admin|admin|teacher|supervisor']);

Route::livewire('/supervisor/logbook/assessment', IndustryAssessmentForm::class)
    ->name('supervisor.logbook.assessment')
    ->middleware(['auth', 'role:supervisor']);

Route::get('/admin/logbook/report/{registration}', function (string $registrationId) {
    $registration = Registration::findOrFail($registrationId);

    return app(CompileLogbookReportAction::class)->download($registration);
})
    ->name('sysadmin.logbook.report')
    ->middleware(['auth', 'role:super_admin|admin|teacher|supervisor']);
