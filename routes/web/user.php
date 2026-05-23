<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\NotificationCenter;
use App\Domain\Auth\Livewire\RecoveryCode;
use App\Domain\User\Http\Controllers\DashboardController;
use App\Domain\User\Http\Controllers\HomeController;
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
