<?php

declare(strict_types=1);

use App\Enrollment\Models\Registration;
use App\Journals\AbsenceRequest\Livewire\AbsenceRequestForm;
use App\Journals\Attendance\Livewire\AttendanceManager;
use App\Journals\Attendance\Livewire\StudentClockIn;
use App\Journals\Logbook\Actions\CompileLogbookReportAction;
use App\Journals\Logbook\Livewire\LogbookEntry;
use App\Journals\Logbook\Livewire\LogbookManager;

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/logbook', LogbookEntry::class)->name('logbook');
        Route::livewire('/attendance', StudentClockIn::class)->name('attendance');
        Route::livewire('/attendance/absence', AbsenceRequestForm::class)->name(
            'attendance.absence',
        );
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

Route::get('/admin/logbook/report/{registration}', function (string $registrationId) {
    $registration = Registration::findOrFail($registrationId);

    return app(CompileLogbookReportAction::class)->download($registration);
})
    ->name('sysadmin.logbook.report')
    ->middleware(['auth', 'role:super_admin|admin|teacher|supervisor']);
