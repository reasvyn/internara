<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\AdminManager;
use App\Domain\Admin\Livewire\AnnouncementManager;
use App\Domain\Admin\Livewire\GdprDeletionLogs;
use App\Domain\Admin\Livewire\MenteeManager;
use App\Domain\Admin\Livewire\MentorManager;
use App\Domain\Admin\Livewire\StudentManager;
use App\Domain\Admin\Livewire\SupervisorManager;
use App\Domain\Admin\Livewire\TeacherManager;
use App\Domain\Admin\Livewire\UserManager;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::prefix('users')->name('users.')->group(function () {
            Route::livewire('/', UserManager::class)->name('index');
            Route::livewire('/admins', AdminManager::class)->name('admins');
            Route::livewire('/students', StudentManager::class)->name('students');
            Route::livewire('/teachers', TeacherManager::class)->name('teachers');
            Route::livewire('/supervisors', SupervisorManager::class)->name('supervisors');
            Route::livewire('/mentors', MentorManager::class)->name('mentors');
            Route::livewire('/mentees', MenteeManager::class)->name('mentees');
        });

        Route::livewire('/gdpr-logs', GdprDeletionLogs::class)->name('gdpr-logs');
    });

Route::livewire('/admin/announcements', AnnouncementManager::class)
    ->name('admin.announcements')
    ->middleware(['auth', 'role:super_admin|admin']);
