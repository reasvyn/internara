<?php

declare(strict_types=1);

use App\Domain\Assessment\Livewire\AssessmentGrading;
use App\Domain\Assessment\Livewire\PresentationSchedule;
use App\Domain\Assessment\Livewire\RubricManager;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/assessments/rubrics', RubricManager::class)->name('assessments.rubrics');
        Route::livewire('/assessments/{registration}/grade', AssessmentGrading::class)->name('assessments.grade');
        Route::livewire('/presentations', PresentationSchedule::class)->name('presentations');
    });
