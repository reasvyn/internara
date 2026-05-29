<?php

declare(strict_types=1);

use App\Domain\Auth\Livewire\AccountLifecycleManager;
use App\Domain\Auth\Livewire\AccountRecovery;
use App\Domain\Auth\Livewire\ActivateAccount;
use App\Domain\Auth\Livewire\ConfirmPassword;
use App\Domain\Auth\Livewire\ForgotPassword;
use App\Domain\Auth\Livewire\Login;
use App\Domain\Auth\Livewire\RecoverySlipManager;
use App\Domain\Auth\Livewire\ResetPassword;
use App\Domain\Registration\Livewire\RegistrationCenter;

Route::middleware(['guest', 'auth.throttle'])->group(function () {
    Route::get('/register', RegistrationCenter::class)->name('register');
    Route::get('/login', Login::class)->name('login');
    Route::get('/activate', ActivateAccount::class)->name('activate');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::get('/recover-account', AccountRecovery::class)->name('recover.account');
});

Route::middleware(['auth', 'auth.throttle'])->group(function () {
    Route::get('/user/confirm-password', ConfirmPassword::class)->name('password.confirm');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::get('/accounts', AccountLifecycleManager::class)->name('accounts.lifecycle');
        Route::get('/recovery-slips', RecoverySlipManager::class)->name('recovery-slips');
    });
