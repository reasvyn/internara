<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Livewire\Dashboard;

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->group(function () {
    Route::get('/admin', Dashboard::class)->name('admin.dashboard');
    Route::get('/admin/jobs', \Modules\Admin\Livewire\JobMonitor::class)->name('admin.jobs');
    Route::get('/admin/reports', \Modules\Report\Livewire\ReportIndex::class)->name(
        'admin.reports',
    );

    // Stakeholder Management
    Route::get('/admin/students', \Modules\User\Livewire\UserManager::class)
        ->name('admin.students')
        ->defaults('targetRole', 'student');
    Route::get('/admin/teachers', \Modules\User\Livewire\UserManager::class)
        ->name('admin.teachers')
        ->defaults('targetRole', 'teacher');
    Route::get('/admin/mentors', \Modules\User\Livewire\UserManager::class)
        ->name('admin.mentors')
        ->defaults('targetRole', 'mentor');

    // Admin Management (SuperAdmin Only)
    Route::middleware(['role:super-admin'])->group(function () {
        Route::get('/admin/administrators', \Modules\User\Livewire\UserManager::class)
            ->name('admin.administrators')
            ->defaults('targetRole', 'admin');
    });
});
