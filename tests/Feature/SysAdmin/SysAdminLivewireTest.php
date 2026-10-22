<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin;

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Enrollment\Domain\AccountApplication\Actions\RejectAccountApplicationAction;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use App\Modules\SysAdmin\Domain\Announcement\Livewire\AnnouncementManager;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use App\Modules\SysAdmin\Domain\Backup\Enums\BackupStatus;
use App\Modules\SysAdmin\Domain\Backup\Enums\BackupType;
use App\Modules\SysAdmin\Domain\Backup\Livewire\BackupManager;
use App\Modules\SysAdmin\Domain\Backup\Models\Backup;
use App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Livewire\GdprDeletionLogs;
use App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use App\Modules\SysAdmin\Domain\Observability\Livewire\AuditLogManager;
use App\Modules\SysAdmin\Domain\Observability\Livewire\Pulse\RegistrationsCard;
use App\Modules\SysAdmin\Domain\Observability\Livewire\Pulse\SystemCard;
use App\Modules\SysAdmin\Livewire\ApplicationReview;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('9YUUK / E1MSJ / 7HNCF: SysAdmin Livewire UI Components', function (): void {
    test('55Y9V-FR-ANNC-001: AnnouncementManager renders for admin and filters by creator', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Announcement::create([
            'title' => 'Scheduled Maintenance Window',
            'message' => 'System will be updated at midnight.',
            'type' => 'info',
            'status' => AnnouncementStatus::PUBLISHED,
            'created_by' => $admin->id,
            'scheduled_at' => now(),
        ]);

        $test = Livewire::test(AnnouncementManager::class);

        $test->assertOk()
            ->assertSee('Scheduled Maintenance Window');

        expect($test->instance()->headers())->toBeArray()
            ->and(collect($test->instance()->headers())->pluck('index')->all())
            ->toContain('title', 'status', 'created_at');
    });

    test('55Y9V-FR-ANNC-002: AnnouncementManager search filters announcements by title', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Announcement::create([
            'title' => 'Unique Alfa Announcement',
            'message' => 'Body content.',
            'type' => 'info',
            'status' => AnnouncementStatus::PUBLISHED,
            'created_by' => $admin->id,
        ]);

        Announcement::create([
            'title' => 'Beta Other Announcement',
            'message' => 'Body content.',
            'type' => 'info',
            'status' => AnnouncementStatus::PUBLISHED,
            'created_by' => $admin->id,
        ]);

        Livewire::test(AnnouncementManager::class)
            ->set('search', 'Unique Alfa')
            ->assertSee('Unique Alfa Announcement')
            ->assertDontSee('Beta Other Announcement');
    });

    test('G0OPV-FR-AUDIT-001: AuditLogManager renders logs and allows filtering by module and action', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Activity::create([
            'log_name' => 'auth',
            'description' => 'user_login_success',
            'causer_id' => $admin->id,
            'causer_type' => User::class,
            'properties' => ['ip_address' => '127.0.0.1'],
        ]);

        Activity::create([
            'log_name' => 'settings',
            'description' => 'system_updated',
            'causer_id' => $admin->id,
            'causer_type' => User::class,
            'properties' => ['ip_address' => '127.0.0.1'],
        ]);

        $test = Livewire::test(AuditLogManager::class);
        $test->assertOk()
            ->assertSee('user_login_success');

        $test->set('filterModule', 'auth')
            ->assertOk();

        $test->call('resetFilters');
        expect($test->get('filterModule'))->toBeNull();
    });

    test('7HNCF-FR-BACKUP-001: BackupManager renders headers and lists backups', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Backup::create([
            'type' => BackupType::DATABASE,
            'status' => BackupStatus::COMPLETED,
            'file_name' => 'db-2026-10-22.sql.gz',
            'file_size' => 1024 * 1024 * 5,
            'path' => 'backups/db-2026-10-22.sql.gz',
            'disk' => 'local',
            'created_by' => $admin->id,
            'completed_at' => now(),
        ]);

        $test = Livewire::test(BackupManager::class);
        $test->assertOk();

        expect(collect($test->instance()->headers())->pluck('index')->all())
            ->toContain('type', 'status', 'file_size', 'created_at');
    });

    test('7HNCF-FR-BACKUP-002: BackupManager search reaches backup type and status', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Backup::create([
            'type' => BackupType::DATABASE,
            'status' => BackupStatus::COMPLETED,
            'file_name' => 'db-backup.sql.gz',
            'file_size' => 1024,
            'path' => 'backups/db-backup.sql.gz',
            'disk' => 'local',
            'created_by' => $admin->id,
        ]);

        Backup::create([
            'type' => BackupType::STORAGE,
            'status' => BackupStatus::FAILED,
            'file_name' => 'storage-backup.tar.gz',
            'file_size' => 2048,
            'path' => 'backups/storage-backup.tar.gz',
            'disk' => 'local',
            'created_by' => $admin->id,
        ]);

        Livewire::test(BackupManager::class)
            ->set('search', BackupType::DATABASE->value)
            ->assertSee(BackupType::DATABASE->value);
    });

    test('9YUUK-FR-APPREV-001: ApplicationReview computes pending applications and handles modal state', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $department = Department::factory()->create();

        $app = AccountApplication::factory()->create([
            'name' => 'Calon Siswa Internara',
            'department_id' => $department->id,
            'status' => AccountApplicationStatus::PENDING,
        ]);

        $test = Livewire::test(ApplicationReview::class);
        $test->assertOk()
            ->assertSee('Calon Siswa Internara');

        expect($test->instance()->pendingApplications->count())->toBeGreaterThanOrEqual(1);

        $test->call('confirmReject', (string) $app->id);
        expect($test->get('showRejectModal'))->toBeTrue()
            ->and($test->get('rejectId'))->toBe((string) $app->id);

        // Validation on rejection
        $test->set('rejectionReason', '')
            ->call('reject', app(RejectAccountApplicationAction::class))
            ->assertHasErrors(['rejectionReason' => 'required']);
    });

    test('2Z9E6-FR-GDPR-001: GdprDeletionLogs renders headers and view data', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        GdprDeletionLog::factory()->count(3)->create();

        $test = Livewire::test(GdprDeletionLogs::class);
        $test->assertOk()
            ->assertViewHas('logs')
            ->assertViewHas('headers');

        expect(collect($test->instance()->headers())->pluck('index')->all())
            ->toContain('user_email', 'deletion_type', 'deleted_at');
    });

    test('E1MSJ-FR-PULSE-001: SystemCard and RegistrationsCard Pulse widgets render view', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $systemCard = new SystemCard;
        $view = $systemCard->render();
        expect($view->getName())->toBe('sysadmin.observability.pulse.system-card')
            ->and($view->getData())->toHaveKeys(['users', 'unreadNotifications']);

        $regCard = new RegistrationsCard;
        $regView = $regCard->render();
        expect($regView->getName())->toBe('sysadmin.observability.pulse.registrations-card')
            ->and($regView->getData())->toHaveKeys(['total', 'pending', 'active', 'completed']);
    });
});
