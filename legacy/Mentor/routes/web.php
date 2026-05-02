<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Mentor\Livewire\Dashboard;
use Modules\Mentor\Livewire\EvaluateIntern;
use Modules\Mentor\Livewire\MentoringManager;

Route::middleware(['auth', 'verified', 'role:mentor'])->group(function () {
    Route::livewire('/mentor', Dashboard::class)->name('mentor.dashboard');
    Route::livewire('/mentor/mentoring/{registrationId}', MentoringManager::class)->name(
        'mentor.mentoring',
    );
    Route::livewire('/mentor/evaluate/{registrationId}', EvaluateIntern::class)->name('mentor.evaluate');
});
