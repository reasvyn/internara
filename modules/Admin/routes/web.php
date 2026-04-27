<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Livewire\AdminIndex;
use Modules\Admin\Livewire\BatchOnboarding;
use Modules\Admin\Livewire\Dashboard;
use Modules\Admin\Livewire\GraduationReadiness;
use Modules\Admin\Livewire\JobMonitor;
use Modules\Mentor\Livewire\MentorIndex;
use Modules\Report\Livewire\ReportIndex;
use Modules\Student\Livewire\StudentIndex;
use Modules\Teacher\Livewire\TeacherIndex;
use Modules\User\Livewire\UserIndex;

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->group(function () {
    Route::get('/admin', Dashboard::class)->name('admin.dashboard');
    Route::get('/admin/jobs', JobMonitor::class)->name('admin.jobs');
    Route::get('/admin/onboarding', BatchOnboarding::class)->name('admin.onboarding');
    Route::get('/admin/reports', ReportIndex::class)->name('admin.reports');
    Route::get('/admin/readiness', GraduationReadiness::class)->name('admin.readiness');

    // Stakeholder Management
    Route::get('/admin/students', StudentIndex::class)->name('admin.students');
    Route::get('/admin/teachers', TeacherIndex::class)->name('admin.teachers');
    Route::get('/admin/mentors', MentorIndex::class)->name('admin.mentors');

    // User Directory (Admin + SuperAdmin)
    Route::get('/admin/users', UserIndex::class)->name('admin.users.index');

    // Admin Management (SuperAdmin Only)
    Route::middleware(['role:super-admin'])->group(function () {
        Route::get('/admin/administrators', AdminIndex::class)->name('admin.administrators');
    });
});
