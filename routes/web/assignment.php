<?php

declare(strict_types=1);

use App\Domain\Assignment\Aggregates\Assignment\Livewire\AssignmentManager as AdminAssignmentManager;
use App\Domain\Assignment\Aggregates\Submission\Livewire\SubmissionGrading;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/assignments', AdminAssignmentManager::class)->name('assignments');
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name('submissions.grading');
    });
