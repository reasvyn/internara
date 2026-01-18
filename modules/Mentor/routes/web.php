<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Mentor\Livewire\Dashboard;

Route::middleware(['auth', 'verified', 'role:mentor'])->group(function () {
    Route::get('/mentor', Dashboard::class)->name('mentor.dashboard');
});