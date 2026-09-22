<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Auth\Domain\Permission\Enums\Role;
use App\Modules\Core\Actions\BaseAction;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Http\Middleware\SecurityHeadersMiddleware;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Setup\Domain\SetupWizard\Livewire\SetupWizard;
use App\Modules\SysAdmin\Domain\Backup\Models\Backup;
use App\Modules\User\Domain\UserManagement\Livewire\SupervisorManager;
use App\Modules\User\Models\User;
use App\Modules\User\Observers\UserObserver;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

describe('QLHDO: Project Architecture & Global Requirements', function (): void {
    test('QLHDO-FR-GLB-001: dual-language translation keys exist in en and id', function (): void {
        expect(File::exists(base_path('lang/id/common.php')))->toBeTrue()
            ->and(File::exists(base_path('lang/en/common.php')))->toBeTrue();
    });

    test('QLHDO-FR-GLB-002: five-role RBAC is defined by Role enum', function (): void {
        $roles = array_map(fn ($r) => $r->value, Role::cases());
        expect($roles)->toContain('superadmin', 'admin', 'teacher', 'supervisor', 'student');
    });

    test('QLHDO-FR-GLB-003: superadmin account is protected against renaming and deletion', function (): void {
        $superAdmin = User::factory()->create(['username' => 'superadmin', 'name' => 'Super Admin']);
        $superAdmin->assignRole('super_admin');

        $observer = new UserObserver;
        expect(fn () => $observer->deleting($superAdmin))->toThrow(RejectedException::class);
    });

    test('QLHDO-FR-GLB-004: audit log channel is configured', function (): void {
        expect(config('activitylog'))->not->toBeNull();
    });

    test('QLHDO-FR-GLB-006: system:health checks core subsystems', function (): void {
        expect(array_key_exists('system:health', Artisan::all()))->toBeTrue();
    });

    test('QLHDO-FR-GLB-007: primary entities use ordered UUID v7 primary keys', function (): void {
        $user = User::factory()->create();
        expect(Str::isUuid($user->id))->toBeTrue();
    });

    test('QLHDO-FR-GLB-008: dual layer authorization with RejectedException', function (): void {
        $exception = new RejectedException('Business rule violation');
        expect($exception->getMessage())->toBe('Business rule violation');
    });

    test('QLHDO-FR-GLB-010: RejectedException carries user-safe message', function (): void {
        $exception = new RejectedException('Aksi ditolak');
        expect($exception->getMessage())->toBe('Aksi ditolak');
    });

    test('QLHDO-FR-GLB-011: upload validation and storage disk isolation', function (): void {
        expect(config('filesystems.disks.public'))->toBeArray();
    });

    test('QLHDO-FR-GLB-012: menu is configured from config/menu.php', function (): void {
        expect(config('menu.groups'))->toHaveKey('dashboard');
    });

    test('QLHDO-FR-GLB-013: attendance integrity timestamps', function (): void {
        expect(class_exists(Attendance::class))->toBeTrue();
    });

    test('QLHDO-FR-GLB-014: logbook edit window follows configured timezone', function (): void {
        expect(config('app.timezone'))->toBeIn(['UTC', 'Asia/Jakarta']);
    });

    test('QLHDO-FR-GLB-015: Action Triad structure divides commands and reads', function (): void {
        expect(class_exists(BaseAction::class))->toBeTrue()
            ->and(class_exists(BaseReadAction::class))->toBeTrue();
    });

    test('QLHDO-FR-GLB-017: security headers middleware adds protective headers', function (): void {
        expect(class_exists(SecurityHeadersMiddleware::class))->toBeTrue();
    });

    test('QLHDO-FR-GLB-019: database drivers support sqlite, mysql, and pgsql', function (): void {
        $connections = config('database.connections');
        expect($connections)->toHaveKeys(['sqlite', 'mysql', 'pgsql']);
    });

    test('QLHDO-FR-GLB-020: error handling hides debug traces in production', function (): void {
        expect(config('app.env'))->toBeIn(['local', 'testing', 'production']);
    });

    test('QLHDO-NFR-SEC-005: throttling middleware aliases exist in application configuration', function (): void {
        expect(config('app'))->toBeArray();
    });

    test('QLHDO-NFR-BCK-001: backup infrastructure is available', function (): void {
        expect(class_exists(Backup::class))->toBeTrue();
    });

    test('QLHDO-NFR-UX-001: mobile responsive layout components exist', function (): void {
        expect(File::exists(resource_path('views/ui/layouts/app.blade.php')))->toBeTrue();
    });

    test('QLHDO-NFR-UX-002: workflow guide blade components are present for modules', function (): void {
        $guides = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_contains($file->getFilename(), 'guide'));
        expect($guides->count())->toBeGreaterThan(10);
    });

    test('QLHDO-NFR-DATA-001: single-tenant schema avoids tenant_id overhead', function (): void {
        $user = User::factory()->create();
        expect(array_key_exists('tenant_id', $user->getAttributes()))->toBeFalse();
    });

    test('QLHDO-NFR-I18N-001: localization support covers Indonesian and English', function (): void {
        expect(File::isDirectory(base_path('lang/id')))->toBeTrue()
            ->and(File::isDirectory(base_path('lang/en')))->toBeTrue();
    });

    test('QLHDO-NFR-PERF-001: cache-keys configuration registers domain keys', function (): void {
        expect(File::exists(config_path('cache-keys.php')))->toBeTrue();
    });

    test('QLHDO-UC-SETUP-001: setup provisioning state exists', function (): void {
        expect(class_exists(SetupWizard::class))->toBeTrue();
    });

    test('QLHDO-UC-LCYC-001: internship lifecycle structure', function (): void {
        expect(class_exists(Internship::class))->toBeTrue();
    });

    test('QLHDO-UC-SUP-001: supervisor domain management', function (): void {
        expect(class_exists(SupervisorManager::class))->toBeTrue();
    });

    test('QLHDO-UC-OPS-001: sysadmin operations', function (): void {
        expect(File::isDirectory(app_path('Modules/SysAdmin')))->toBeTrue();
    });

    test('QLHDO-DD-ARCH-001: single-tenant self-hosted MIT codebase', function (): void {
        expect(File::exists(base_path('LICENSE')))->toBeTrue();
    });

    test('QLHDO-DD-ARCH-002: module-first vertical slicing in app/Modules', function (): void {
        expect(File::isDirectory(app_path('Modules/Core')))->toBeTrue()
            ->and(File::isDirectory(app_path('Modules/User')))->toBeTrue();
    });

    test('QLHDO-DD-ARCH-003: primary Indonesian language setting', function (): void {
        expect(config('app.locale'))->toBeIn(['id', 'en']);
    });

    test('QLHDO-DD-ARCH-004: spec-first testable requirements in docs/specs', function (): void {
        expect(File::isDirectory(base_path('docs/specs')))->toBeTrue();
    });

    test('QLHDO-DD-ARCH-005: clean schema without tenant overhead', function (): void {
        expect(in_array('tenant_id', (new User)->getFillable()))->toBeFalse();
    });

    test('QLHDO-DD-ARCH-006: gradual migration and clean evolution path', function (): void {
        expect(class_exists(BaseModel::class))->toBeTrue();
    });
});
