<?php

declare(strict_types=1);

use App\Domain\Evaluation\Livewire\MentorEvaluationManager;

Route::livewire('/evaluate', MentorEvaluationManager::class)
    ->name('mentor.evaluate')
    ->middleware('auth');
