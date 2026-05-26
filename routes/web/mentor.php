<?php

declare(strict_types=1);

use App\Domain\Assignment\Livewire\SubmissionGrading;
use App\Domain\Mentor\Livewire\AssessInternship;
use App\Domain\Mentor\Livewire\ReportNotes;
use App\Domain\Mentor\Livewire\ReportReview;
use App\Domain\Mentor\Livewire\Supervision\SupervisorLogManager;

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
    });

Route::prefix('supervisor')
    ->name('supervisor.')
    ->middleware(['auth', 'role:supervisor'])
    ->group(function () {
        Route::livewire('/reports/notes', ReportNotes::class)->name('reports.notes');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships/reports/review', ReportReview::class)->name('reports.review');
    });
