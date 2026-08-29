<?php

declare(strict_types=1);

use App\Modules\SysAdmin\Domain\Announcement\Livewire\AnnouncementManager;
use App\Modules\SysAdmin\Domain\Backups\Livewire\BackupManager;
use App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Livewire\GdprDeletionLogs;
use App\Modules\SysAdmin\Domain\Observability\Livewire\AccountCloneDetector;
use App\Modules\SysAdmin\Domain\Observability\Livewire\AuditLogManager;
use App\Modules\SysAdmin\Http\Controllers\AccountSlipController;
use App\Modules\SysAdmin\Http\Controllers\CronController;
use App\Modules\SysAdmin\Livewire\ApplicationReview;
use App\Modules\User\Domain\UserManagement\Livewire\AdminManager;
use App\Modules\User\Domain\UserManagement\Livewire\StudentManager;
use App\Modules\User\Domain\UserManagement\Livewire\SupervisorManager;
use App\Modules\User\Domain\UserManagement\Livewire\TeacherManager;
use App\Modules\User\Domain\UserManagement\Livewire\UserManager;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::prefix('users')
            ->name('users.')
            ->group(function () {
                Route::get('/', UserManager::class)->name('index');
                Route::get('/admins', AdminManager::class)->name('admins');
                Route::get('/students', StudentManager::class)->name('students');
                Route::get('/teachers', TeacherManager::class)->name('teachers');
                Route::get('/supervisors', SupervisorManager::class)->name('supervisors');

                Route::get('/{user}/account-slip', [AccountSlipController::class, 'download'])->name('account-slip');

                Route::get('/account-slips/download', [AccountSlipController::class, 'downloadBatch'])->name('account-slips.batch');
            });

        Route::get('/gdpr-logs', GdprDeletionLogs::class)->name('gdpr-logs');
        Route::get('/audit-log', AuditLogManager::class)->name('audit-log');
        Route::get('/accounts/clones', AccountCloneDetector::class)->name('accounts.clones');

        Route::get('/backups', BackupManager::class)->name('backups');
    });

Route::get('/admin/applications', ApplicationReview::class)
    ->name('sysadmin.applications')
    ->middleware(['auth', 'role:super_admin|admin']);

Route::get('/admin/announcements', AnnouncementManager::class)
    ->name('sysadmin.announcements')
    ->middleware(['auth', 'role:super_admin|admin']);

// ──────────────────────────────────────────────
// Cron / system
// ──────────────────────────────────────────────

Route::get('/cron/{secret}', CronController::class)
    ->middleware('throttle:10,1')
    ->name('cron');
