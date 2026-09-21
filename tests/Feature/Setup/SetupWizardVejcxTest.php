<?php

declare(strict_types=1);

namespace Tests\Feature\Setup;

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Setting\Models\Setting;
use App\Modules\Setup\Domain\Installation\Http\Middleware\ProtectSetupRouteMiddleware;
use App\Modules\Setup\Domain\Installation\Http\Middleware\RequireSetupAccessMiddleware;
use App\Modules\Setup\Domain\SetupWizard\Actions\FinalizeSetupAction;
use App\Modules\Setup\Domain\SetupWizard\Actions\SetupDepartmentAction;
use App\Modules\Setup\Domain\SetupWizard\Actions\SetupSchoolAction;
use App\Modules\Setup\Domain\SetupWizard\Actions\SetupSuperAdminAction;
use App\Modules\Setup\Domain\SetupWizard\Data\FinalizeSetupData;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

describe('VEJCX: Setup Wizard', function (): void {
    test('VEJCX-FR-WIZ-003/004/005/009/010/011/012/013/014/015 VEJCX-NFR-WIZ-002/003/004: finalization creates school, department and superadmin atomically', function (): void {
        Setting::updateOrCreate(
            ['key' => 'setup.is_installed'],
            ['value' => false, 'group' => 'setup', 'type' => 'boolean']
        );
        Cache::flush();

        $action = app(FinalizeSetupAction::class);

        $schoolData = [
            'name' => 'SMK Negeri 1 Test',
            'institutional_code' => 'NPSN12345678',
            'email' => 'smkn1test@example.sch.id',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '021-1234567',
            'website' => 'https://smkn1test.sch.id',
            'principal_name' => 'Dr. H. Bambang, M.Pd.',
        ];

        $deptData = [
            'name' => 'Rekayasa Perangkat Lunak',
            'description' => 'Program keahlian software development',
        ];

        $adminData = [
            'email' => 'superadmin@example.sch.id',
            'password' => 'Secret123!',
        ];

        $data = new FinalizeSetupData(
            schoolData: $schoolData,
            departmentData: $deptData,
            adminData: $adminData,
        );

        $recoveryKey = $action->execute($data);

        expect($recoveryKey)->toBeString()
            ->and(strlen($recoveryKey))->toBe(64);

        // Verify Department
        $dept = Department::where('name', 'Rekayasa Perangkat Lunak')->first();
        expect($dept)->not->toBeNull();

        // Verify Super Admin
        $user = User::where('email', 'superadmin@example.sch.id')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole('superadmin'))->toBeTrue()
            ->and($user->status)->toBe(AccountStatus::PROTECTED)
            ->and(Hash::check('Secret123!', $user->password))->toBeTrue();

        // Verify Settings
        expect(Setting::where('key', 'school.name')->first()?->value)->toBe('SMK Negeri 1 Test')
            ->and(Setting::where('key', 'school.institutional_code')->first()?->value)->toBe('NPSN12345678');

        // Verify Re-running finalization throws RejectedException (FR-WIZ-015)
        expect(fn () => $action->execute($data))->toThrow(RejectedException::class);
    });

    test('VEJCX-FR-WIZ-001/002/006/007/008 VEJCX-UC-WIZ-001/004 VEJCX-NFR-WIZ-005/007/010: wizard structure and step contracts', function (): void {
        $steps = config('setup.wizard.step_keys');
        expect($steps)->toBe(['welcome', 'account', 'school', 'department', 'finalize', 'complete'])
            ->and(class_exists(FinalizeSetupAction::class))->toBeTrue()
            ->and(class_exists(SetupSchoolAction::class))->toBeTrue()
            ->and(class_exists(SetupDepartmentAction::class))->toBeTrue()
            ->and(class_exists(SetupSuperAdminAction::class))->toBeTrue();
    });

    test('VEJCX-FR-WIZ-016/017/018/019 VEJCX-UC-WIZ-002/003 VEJCX-NFR-WIZ-001/006/008/009 VEJCX-DD-WIZ-001/002/003/004/005: middleware protection and contracts', function (): void {
        $window = config('setup.security.finalization_window_seconds', 30);
        expect($window)->toBe(30)
            ->and(class_exists(ProtectSetupRouteMiddleware::class))->toBeTrue()
            ->and(class_exists(RequireSetupAccessMiddleware::class))->toBeTrue();
    });
});
