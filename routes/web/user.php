<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccountRecovery\Livewire\AccountRecovery;
use App\Modules\Auth\Domain\AccountRecovery\Livewire\RecoveryCode;
use App\Modules\Auth\Domain\AccountRecovery\Livewire\RecoverySlipManager;
use App\Modules\Auth\Domain\Password\Livewire\ConfirmPassword;
use App\Modules\Auth\Domain\Password\Livewire\ForgotPassword;
use App\Modules\Auth\Domain\Password\Livewire\ResetPassword;
use App\Modules\User\Domain\AccountStatus\Livewire\AccountLifecycleManager;
use App\Modules\User\Domain\Dashboard\Livewire\AdminDashboard;
use App\Modules\User\Domain\Dashboard\Livewire\StudentDashboard;
use App\Modules\User\Domain\Dashboard\Livewire\SupervisorDashboard;
use App\Modules\User\Domain\Dashboard\Livewire\TeacherDashboard;
use App\Modules\User\Domain\Dashboard\Livewire\UserDashboard;
use App\Modules\User\Http\Controllers\AuthController;
use App\Modules\User\Http\Controllers\DashboardController;
use App\Modules\User\Livewire\HomePage;
use App\Modules\User\Domain\Notifications\Livewire\NotificationCenter;
use App\Modules\User\Domain\Profile\Livewire\ProfileEditor;

Route::livewire('/', HomePage::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::livewire('/my-dashboard', UserDashboard::class)->name('user.dashboard');
    Route::livewire('/profile', ProfileEditor::class)->name('profile');
    Route::livewire('/profile/recovery', RecoveryCode::class)->name('profile.recovery');
    Route::livewire('/notifications', NotificationCenter::class)->name('notifications');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/dashboard', AdminDashboard::class)->name('dashboard');
    });

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/dashboard', StudentDashboard::class)->name('dashboard');
    });

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher'])
    ->group(function () {
        Route::livewire('/dashboard', TeacherDashboard::class)->name('dashboard');
    });

Route::prefix('supervisor')
    ->name('supervisor.')
    ->middleware(['auth', 'role:supervisor'])
    ->group(function () {
        Route::livewire('/dashboard', SupervisorDashboard::class)->name('dashboard');
    });

Route::middleware(['guest', 'auth.throttle'])->group(function () {
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::get('/recover-account', AccountRecovery::class)->name('recover.account');
});

Route::middleware(['auth', 'auth.throttle'])->group(function () {
    Route::get('/user/confirm-password', ConfirmPassword::class)->name('password.confirm');
});

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::get('/accounts', AccountLifecycleManager::class)->name('accounts.lifecycle');
        Route::get('/recovery-slips', RecoverySlipManager::class)->name('recovery-slips');
    });
