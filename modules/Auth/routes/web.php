<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Livewire\AcceptInvitation;
use Modules\Auth\Livewire\ClaimAccount;
use Modules\Auth\Livewire\ForgotPassword;
use Modules\Auth\Livewire\Login;
use Modules\Auth\Livewire\ResetPassword;
use Modules\Auth\Registration\Livewire\Register;
use Modules\Auth\Verification\Livewire\VerificationNotice;
use Modules\Auth\Verification\Livewire\VerifyEmail;

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
    Route::get('login', Login::class)
        ->middleware(['guest', 'throttle:auth'])
        ->name('login');

    Route::post('logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })
        ->middleware(['auth'])
        ->name('logout');

    Route::get('forgot-password', ForgotPassword::class)
        ->middleware(['guest', 'throttle:auth'])
        ->name('forgot-password');

    Route::get('reset-password/{token}', ResetPassword::class)
        ->middleware(['guest', 'throttle:auth'])
        ->name('password.reset');

    // Account claim — for provisioned accounts without email (no auth required)
    Route::get('claim', ClaimAccount::class)
        ->middleware(['guest', 'throttle:6,1'])
        ->name('claim-account');

    // Admin account invitation — email link flow for privileged accounts
    Route::get('invitation/{token}', AcceptInvitation::class)
        ->middleware(['guest', 'throttle:10,1'])
        ->name('invitation.accept');

    Route::get('register', Register::class)
        ->middleware(['guest', 'throttle:registration'])
        ->name('register');

    Route::get('email/verify/{id}/{hash}', VerifyEmail::class)
        ->middleware(['auth', 'signed', 'throttle:auth'])
        ->name('verification.verify');

    Route::get('email/verify', VerificationNotice::class)
        ->middleware(['auth', 'throttle:auth'])
        ->name('verification.notice');
});
