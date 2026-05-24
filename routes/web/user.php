<?php

declare(strict_types=1);

use App\Domain\Auth\Livewire\RecoveryCode;
use App\Domain\User\Http\Controllers\DashboardController;
use App\Domain\User\Http\Controllers\HomeController;
use App\Domain\User\Livewire\Dashboards\AdminDashboard;
use App\Domain\User\Livewire\Dashboards\StudentDashboard;
use App\Domain\User\Livewire\Dashboards\SupervisorDashboard;
use App\Domain\User\Livewire\Dashboards\TeacherDashboard;
use App\Domain\User\Livewire\NotificationCenter;
use App\Domain\User\Livewire\ProfileEditor;
use App\Domain\User\Livewire\UserDashboard;

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
    ->name('admin.')
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
