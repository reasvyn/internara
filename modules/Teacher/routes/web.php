<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Teacher\Livewire\Dashboard;

Route::middleware(['auth', 'verified', 'role:teacher'])->group(function () {
    Route::get('/teacher', Dashboard::class)->name('teacher.dashboard');
});