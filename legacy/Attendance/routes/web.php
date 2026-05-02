<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Livewire\AttendanceIndex;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/attendance', AttendanceIndex::class)->name('attendance.index');
});
