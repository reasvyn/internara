<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Account\Livewire\ActivateAccount;
use App\Modules\Auth\Domain\Login\Livewire\Login;

Route::middleware(['guest', 'auth.throttle'])->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/activate', ActivateAccount::class)->name('activate');
});
