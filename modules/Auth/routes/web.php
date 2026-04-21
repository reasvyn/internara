<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::get('login', Modules\Auth\Livewire\Login::class)
        ->middleware(['guest', 'throttle:auth'])
        ->name('login');

    Route::post('logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->middleware(['auth'])->name('logout');

    Route::get('forgot-password', Modules\Auth\Livewire\ForgotPassword::class)
        ->middleware(['guest', 'throttle:auth'])
        ->name('forgot-password');

    Route::get('reset-password/{token}', Modules\Auth\Livewire\ResetPassword::class)
        ->middleware(['guest', 'throttle:auth'])
        ->name('password.reset');

    // Account claim — for provisioned accounts without email (no auth required)
    Route::get('claim', Modules\Auth\Livewire\ClaimAccount::class)
        ->middleware(['guest', 'throttle:6,1'])
        ->name('claim-account');

    // Admin account invitation — email link flow for privileged accounts
    Route::get('invitation/{token}', Modules\Auth\Livewire\AcceptInvitation::class)
        ->middleware(['guest', 'throttle:10,1'])
        ->name('invitation.accept');

    Route::get('register', Modules\Auth\Registration\Livewire\Register::class)
        ->middleware(['guest', 'throttle:registration'])
        ->name('register');

    Route::get('email/verify/{id}/{hash}', Modules\Auth\Verification\Livewire\VerifyEmail::class)
        ->middleware(['auth', 'signed', 'throttle:auth'])
        ->name('verification.verify');

    Route::get('email/verify', Modules\Auth\Verification\Livewire\VerificationNotice::class)
        ->middleware(['auth', 'throttle:auth'])
        ->name('verification.notice');
});
