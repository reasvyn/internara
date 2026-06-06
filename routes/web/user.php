<?php

declare(strict_types=1);

use App\User\AccountRecovery\Livewire\AccountRecovery;
use App\User\AccountRecovery\Livewire\RecoveryCode;
use App\User\AccountRecovery\Livewire\RecoverySlipManager;
use App\User\AccountStatus\Livewire\AccountLifecycleManager;
use App\User\ActivationToken\Livewire\ActivateAccount;
use App\User\Dashboard\Livewire\AdminDashboard;
use App\User\Dashboard\Livewire\StudentDashboard;
use App\User\Dashboard\Livewire\SupervisorDashboard;
use App\User\Dashboard\Livewire\TeacherDashboard;
use App\User\Dashboard\Livewire\UserDashboard;
use App\User\Http\Controllers\DashboardController;
use App\User\Http\Controllers\HomeController;
use App\User\Login\Livewire\Login;
use App\User\Notifications\Livewire\NotificationCenter;
use App\User\Password\Livewire\ConfirmPassword;
use App\User\Password\Livewire\ForgotPassword;
use App\User\Password\Livewire\ResetPassword;
use App\User\Profile\Livewire\ProfileEditor;

Route::get('/', HomeController::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::livewire('/my-dashboard', UserDashboard::class)->name('user.dashboard');
    Route::livewire('/profile', ProfileEditor::class)->name('profile');
    Route::livewire('/profile/recovery', RecoveryCode::class)->name('profile.recovery');
    Route::livewire('/notifications', NotificationCenter::class)->name('notifications');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
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
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::get('/accounts', AccountLifecycleManager::class)->name('accounts.lifecycle');
        Route::get('/recovery-slips', RecoverySlipManager::class)->name('recovery-slips');
    });
