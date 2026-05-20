<?php

declare(strict_types=1);

use App\Domain\Assignment\Livewire\AssignmentManager as AdminAssignmentManager;
use App\Domain\Assignment\Livewire\SubmissionGrading;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/assignments', AdminAssignmentManager::class)->name('assignments');
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name('submissions.grading');
    });
