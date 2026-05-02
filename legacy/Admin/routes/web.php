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
    Route::livewire('/admin', Dashboard::class)->name('admin.dashboard');
    Route::livewire('/admin/jobs', JobMonitor::class)->name('admin.jobs');
    Route::livewire('/admin/onboarding', BatchOnboarding::class)->name('admin.onboarding');
    Route::livewire('/admin/reports', ReportIndex::class)->name('admin.reports');
    Route::livewire('/admin/readiness', GraduationReadiness::class)->name('admin.readiness');

    // Stakeholder Management
    Route::livewire('/admin/students', StudentIndex::class)->name('admin.students');
    Route::livewire('/admin/teachers', TeacherIndex::class)->name('admin.teachers');
    Route::livewire('/admin/mentors', MentorIndex::class)->name('admin.mentors');

    // User Directory (Admin + SuperAdmin)
    Route::livewire('/admin/users', UserIndex::class)->name('admin.users.index');

    // Admin Management (SuperAdmin Only)
    Route::middleware(['role:super-admin'])->group(function () {
        Route::livewire('/admin/administrators', AdminIndex::class)->name('admin.administrators');
    });
});
