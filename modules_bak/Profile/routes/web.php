<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Profile\Livewire\Index;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', Index::class)->name('profile.index');
});
