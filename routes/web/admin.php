<?php

declare(strict_types=1);

use App\Domain\Admin\Actions\GenerateAccountSlipAction;
use App\Domain\Admin\Livewire\AdminManager;
use App\Domain\Admin\Livewire\AnnouncementManager;
use App\Domain\Admin\Livewire\GdprDeletionLogs;
use App\Domain\Admin\Livewire\MenteeManager;
use App\Domain\Admin\Livewire\MentorManager;
use App\Domain\Admin\Livewire\StudentManager;
use App\Domain\Admin\Livewire\SupervisorManager;
use App\Domain\Admin\Livewire\TeacherManager;
use App\Domain\Admin\Livewire\UserManager;
use App\Domain\User\Models\User;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', UserManager::class)->name('index');
            Route::get('/admins', AdminManager::class)->name('admins');
            Route::get('/students', StudentManager::class)->name('students');
            Route::get('/teachers', TeacherManager::class)->name('teachers');
            Route::get('/supervisors', SupervisorManager::class)->name('supervisors');
            Route::get('/mentors', MentorManager::class)->name('mentors');
            Route::get('/mentees', MenteeManager::class)->name('mentees');

            Route::get('/{user}/account-slip', function (User $user) {
                return app(GenerateAccountSlipAction::class)->download($user);
            })->name('account-slip');

            Route::get('/account-slips/download', function () {
                $ids = explode(',', request()->string('ids', ''));
                $users = User::whereIn('id', $ids)->get();

                return app(GenerateAccountSlipAction::class)->downloadBatch($users->all());
            })->name('account-slips.batch');
        });

        Route::get('/gdpr-logs', GdprDeletionLogs::class)->name('gdpr-logs');
    });

Route::get('/admin/announcements', AnnouncementManager::class)
    ->name('admin.announcements')
    ->middleware(['auth', 'role:super_admin|admin']);
