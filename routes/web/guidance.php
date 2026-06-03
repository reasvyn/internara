<?php

declare(strict_types=1);

use App\Domain\Assessment\Aggregates\Assessment\Livewire\AssessmentView;
use App\Domain\Assignment\Aggregates\Submission\Livewire\SubmitAssignment;
use App\Domain\Assignment\Livewire\SubmissionGrading;
use App\Domain\Certification\Aggregates\Certificate\Livewire\StudentCertificates;
use App\Domain\Enrollment\Livewire\StudentPlacementChangeRequest;
use App\Domain\Guidance\Aggregates\Handbook\Livewire\HandbookIndex;
use App\Domain\Guidance\Aggregates\Handbook\Livewire\HandbookManager;
use App\Domain\Guidance\Aggregates\Mentor\Livewire\AssessInternship;
use App\Domain\Guidance\Aggregates\Mentor\Livewire\EvaluateMentor;
use App\Domain\Guidance\Aggregates\Mentor\Livewire\MentorProfileManager;
use App\Domain\Guidance\Aggregates\Mentor\Livewire\ReportNotes;
use App\Domain\Guidance\Aggregates\Mentor\Livewire\ReportReview;
use App\Domain\Guidance\Aggregates\SupervisionLog\Livewire\SupervisionManager;
use App\Domain\Guidance\Aggregates\SupervisionLog\Livewire\SupervisorLogManager;
use App\Domain\Incident\Aggregates\IncidentReport\Livewire\IncidentForm;
use App\Domain\Journals\Aggregates\AbsenceRequest\Livewire\AbsenceRequestForm;
use App\Domain\Journals\Aggregates\Attendance\Livewire\StudentClockIn;
use App\Domain\Journals\Aggregates\Logbook\Livewire\LogbookEntry;
use App\Domain\Reports\Aggregates\Report\Livewire\ReportWriter;

// Student routes (previously in mentee.php)
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

// Supervision & Teacher/Supervisor routes (previously in mentor.php)
Route::prefix('supervision')
    ->name('supervision.')
    ->middleware(['auth', 'role:teacher|supervisor'])
    ->group(function () {
        Route::livewire('/logs', SupervisorLogManager::class)->name('logs');
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name('submissions.grading');
    });

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher'])
    ->group(function () {
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name('submissions.grading');
        Route::livewire('/assess-internship', AssessInternship::class)->name('assess-internship');
        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks');
    });

Route::prefix('supervisor')
    ->name('supervisor.')
    ->middleware(['auth', 'role:supervisor'])
    ->group(function () {
        Route::livewire('/reports/notes', ReportNotes::class)->name('reports.notes');
        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/handbooks', HandbookManager::class)->name('handbooks.index');
        Route::livewire('/internships/reports/review', ReportReview::class)->name('reports.review');
        Route::livewire('/mentors/profiles', MentorProfileManager::class)->name('mentors.profiles');
    });
