<?php

declare(strict_types=1);

use App\SysAdmin\Account\Actions\GenerateAccountSlipAction;
use App\SysAdmin\Account\Livewire\AdminManager;
use App\SysAdmin\Account\Livewire\StudentManager;
use App\SysAdmin\Account\Livewire\SupervisorManager;
use App\SysAdmin\Account\Livewire\TeacherManager;
use App\SysAdmin\Account\Livewire\UserManager;
use App\SysAdmin\Announcement\Livewire\AnnouncementManager;
use App\SysAdmin\GdprDeletionLog\Livewire\GdprDeletionLogs;
use App\SysAdmin\Livewire\AccountCloneDetector;
use App\SysAdmin\Livewire\ApplicationReview;
use App\SysAdmin\Livewire\AuditLogManager;
use App\SysAdmin\Setting\Livewire\SystemSetting;
use App\SysAdmin\Setup\Livewire\SetupWizard;
use App\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Laravel\Pulse\Pulse;

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
        Route::get('/audit-log', AuditLogManager::class)->name('audit-log');
        Route::get('/accounts/clones', AccountCloneDetector::class)->name('accounts.clones');
    });

Route::get('/admin/applications', ApplicationReview::class)
    ->name('sysadmin.applications')
    ->middleware(['auth', 'role:super_admin|admin']);

Route::get('/admin/announcements', AnnouncementManager::class)
    ->name('sysadmin.announcements')
    ->middleware(['auth', 'role:super_admin|admin']);

Route::livewire('/admin/settings', SystemSetting::class)
    ->name('admin.settings')
    ->middleware(['auth', 'role:super_admin']);

// ──────────────────────────────────────────────
// Setup submodule (clustered with its own middleware)
// ──────────────────────────────────────────────

Route::middleware('setup.protected')->group(function () {
    Route::get('/setup', SetupWizard::class)->name('setup');
    Route::post('/setup', fn () => redirect()->route('setup'));
});

Route::post('/setup/cleanup', function () {
    Session::forget(['setup.form_data', 'setup.authorized']);

    return response()->noContent();
})->name('setup.cleanup');

// ──────────────────────────────────────────────
// Cron / system
// ──────────────────────────────────────────────

Route::get('/cron/{secret}', function (string $secret) {
    if ($secret !== config('app.cron_secret')) {
        abort(403, 'Invalid cron secret.');
    }

    $output = [];

    $exitCode = Artisan::call('schedule:run');
    $output['schedule:run'] = $exitCode;

    if (class_exists(Pulse::class)) {
        $exitCode = Artisan::call('pulse:check');
        $output['pulse:check'] = $exitCode;
    }

    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'commands' => $output,
    ]);
})->name('cron');
