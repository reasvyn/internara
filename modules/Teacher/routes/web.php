<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Teacher\Livewire\AssessInternship;
use Modules\Teacher\Livewire\Dashboard;

Route::middleware(['auth', 'verified', 'role:teacher'])->group(function () {
    Route::get('/teacher', Dashboard::class)->name('teacher.dashboard');
    Route::get('/teacher/assess/{registrationId}', AssessInternship::class)->name('teacher.assess');
    Route::get('/teacher/reports', \Modules\Report\Livewire\ReportIndex::class)->name(
        'teacher.reports',
    );
});
