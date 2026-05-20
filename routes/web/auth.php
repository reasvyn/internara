<?php

declare(strict_types=1);

use App\Domain\Auth\Livewire\AccountLifecycleManager;
use App\Domain\Auth\Livewire\AccountRecovery;
use App\Domain\Auth\Livewire\ConfirmPassword;
use App\Domain\Auth\Livewire\ForgotPassword;
use App\Domain\Auth\Livewire\Login;
use App\Domain\Auth\Livewire\RecoverySlipManager;
use App\Domain\Auth\Livewire\ResetPassword;
use App\Domain\Registration\Livewire\RegistrationCenter;

Route::middleware('guest')->group(function () {
    Route::livewire('/register', RegistrationCenter::class)->name('register');
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::livewire('/recover-account', AccountRecovery::class)->name('recover.account');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/user/confirm-password', ConfirmPassword::class)->name('password.confirm');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/accounts', AccountLifecycleManager::class)->name('accounts.lifecycle');
        Route::livewire('/recovery-slips', RecoverySlipManager::class)->name('recovery-slips');
    });
