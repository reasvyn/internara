<?php

declare(strict_types=1);

use App\Domain\Evaluation\Livewire\MentorEvaluationManager;

Route::livewire('/evaluate', MentorEvaluationManager::class)
    ->name('mentor.evaluate')
    ->middleware('auth');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/evaluations', MentorEvaluationManager::class)->name('evaluations');
    });
