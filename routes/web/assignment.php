<?php

declare(strict_types=1);

use App\Assignment\Livewire\AssignmentManager as AdminAssignmentManager;
use App\Assignment\Submission\Livewire\SubmissionGrading;
use App\Assignment\Submission\Livewire\SubmitAssignment;

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/assignments', SubmitAssignment::class)->name('assignments');
    });

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/assignments', AdminAssignmentManager::class)->name('assignments');
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name(
            'submissions.grading',
        );
    });

Route::middleware(['auth'])
    ->group(function () {
        Route::get('/assignments/{assignment}', function (App\Assignment\Models\Assignment $assignment) {
            return redirect()->route('sysadmin.assignments');
        })->name('assignment.show');
    });

Route::prefix('supervision')
    ->name('supervision.')
    ->middleware(['auth', 'role:teacher|supervisor'])
    ->group(function () {
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name(
            'submissions.grading',
        );
    });

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher'])
    ->group(function () {
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name(
            'submissions.grading',
        );
    });
