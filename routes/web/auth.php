<?php

declare(strict_types=1);

use App\Auth\Account\Livewire\ActivateAccount;
use App\Auth\Login\Livewire\Login;

Route::middleware(['guest', 'auth.throttle'])->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/activate', ActivateAccount::class)->name('activate');
});
