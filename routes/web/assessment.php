<?php

declare(strict_types=1);

use App\Assessment\Livewire\AssessmentGrading;
use App\Assessment\Livewire\AssessmentView;
use App\Assessment\Presentation\Livewire\PresentationSchedule;
use App\Assessment\Rubric\Livewire\RubricManager;

Route::middleware('auth')->group(function () {
    Route::livewire('/assessments', AssessmentView::class)->name('assessments');
});

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/assessments/rubrics', RubricManager::class)->name('assessments.rubrics');
        Route::livewire('/assessments/{registration}/grade', AssessmentGrading::class)->name(
            'assessments.grade',
        );
        Route::livewire('/presentations', PresentationSchedule::class)->name('presentations');
    });
