<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Student\Livewire\Dashboard;

Route::middleware(['auth', 'verified', 'role:student'])->group(function () {
    Route::get('/student', Dashboard::class)->name('student.dashboard');
});
