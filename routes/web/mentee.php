<?php

declare(strict_types=1);

use App\Domain\Assessment\Livewire\AssessmentView;
use App\Domain\Assignment\Livewire\SubmitAssignment;
use App\Domain\Attendance\Livewire\AbsenceRequestForm;
use App\Domain\Attendance\Livewire\StudentClockIn;
use App\Domain\Certificate\Livewire\StudentCertificates;
use App\Domain\Guidance\Livewire\HandbookIndex;
use App\Domain\Incident\Livewire\IncidentForm;
use App\Domain\Internship\Livewire\ReportWriter;
use App\Domain\Logbook\Livewire\LogbookEntry;
use App\Domain\Mentor\Livewire\EvaluateMentor;
use App\Domain\Mentor\Livewire\Supervision\SupervisionManager;
use App\Domain\Placement\Livewire\StudentPlacementChangeRequest;

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/logbook', LogbookEntry::class)->name('logbook');
        Route::livewire('/assignments', SubmitAssignment::class)->name('assignments');
        Route::livewire('/supervision', SupervisionManager::class)->name('supervision');
        Route::livewire('/assessments', AssessmentView::class)->name('assessments');
        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks');
        Route::livewire('/attendance', StudentClockIn::class)->name('attendance');
        Route::livewire('/attendance/absence', AbsenceRequestForm::class)->name('attendance.absence');
        Route::livewire('/incidents/report', IncidentForm::class)->name('incidents.report');
        Route::livewire('/reports', ReportWriter::class)->name('reports');
        Route::livewire('/internships/placement-change', StudentPlacementChangeRequest::class)->name('internships.placement-change');
        Route::livewire('/certificates', StudentCertificates::class)->name('certificates');
        Route::livewire('/mentors/evaluate', EvaluateMentor::class)->name('mentors.evaluate');
    });
