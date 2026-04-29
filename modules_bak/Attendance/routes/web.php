<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Livewire\AttendanceIndex;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', AttendanceIndex::class)->name('attendance.index');
});
