<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Livewire\Dashboard;

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->group(function () {
    Route::get('/admin', Dashboard::class)->name('admin.dashboard');
    Route::get('/admin/jobs', \Modules\Admin\Livewire\JobMonitor::class)->name('admin.jobs');
    Route::get('/admin/onboarding', \Modules\Admin\Livewire\BatchOnboarding::class)->name(
        'admin.onboarding',
    );
    Route::get('/admin/reports', \Modules\Report\Livewire\ReportIndex::class)->name(
        'admin.reports',
    );
    Route::get('/admin/readiness', \Modules\Admin\Livewire\GraduationReadiness::class)->name(
        'admin.readiness',
    );

    // Comprehensive User Management
    Route::get('/admin/users', \Modules\User\Livewire\UserManager::class)->name('admin.users.index');

    // Stakeholder Management
    Route::get('/admin/students', \Modules\Student\Livewire\StudentManager::class)->name('admin.students');
    Route::get('/admin/teachers', \Modules\Teacher\Livewire\TeacherManager::class)->name('admin.teachers');
    Route::get('/admin/mentors', \Modules\Mentor\Livewire\MentorManager::class)->name('admin.mentors');

    // Admin Management (SuperAdmin Only)
    Route::middleware(['role:super-admin'])->group(function () {
        Route::get('/admin/administrators', \Modules\Admin\Livewire\AdminManager::class)->name('admin.administrators');
    });
});
