<?php

declare(strict_types=1);

use App\Domain\Assessment\Aggregates\Assessment\Livewire\AssessmentGrading;
use App\Domain\Assessment\Aggregates\Presentation\Livewire\PresentationSchedule;
use App\Domain\Assessment\Aggregates\Rubric\Livewire\RubricManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/assessments/rubrics', RubricManager::class)->name('assessments.rubrics');
        Route::livewire('/assessments/{registration}/grade', AssessmentGrading::class)->name('assessments.grade');
        Route::livewire('/presentations', PresentationSchedule::class)->name('presentations');
    });

use App\Domain\Evaluation\Aggregates\Evaluation\Livewire\MentorEvaluationManager;

Route::livewire('/evaluate', MentorEvaluationManager::class)
    ->name('mentor.evaluate')
    ->middleware('auth');

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/evaluations', MentorEvaluationManager::class)->name('evaluations');
    });
