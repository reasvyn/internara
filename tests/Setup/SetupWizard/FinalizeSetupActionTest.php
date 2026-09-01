<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Auth\Domain\Permissions\Enums\Role as RoleEnum;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Settings\Services\Settings as SettingsService;
use App\Modules\Setup\Domain\SetupWizard\Actions\FinalizeSetupAction;
use App\Modules\Setup\Domain\SetupWizard\Data\FinalizeSetupData;
use App\Modules\Setup\Domain\SetupWizard\Events\SetupFinalized;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

function vejcxFinalizeData(array $overrides = []): FinalizeSetupData
{
    return new FinalizeSetupData(
        schoolData: array_merge([
            'name' => 'SMK Negeri 1 Bandung',
            'institutional_code' => '20212345',
            'email' => 'info@smkn1bdg.sch.id',
            'address' => 'Jl. Soekarno Hatta No. 1',
            'phone' => '',
            'website' => '',
            'principal_name' => '',
        ], $overrides['schoolData'] ?? []),
        departmentData: array_merge([
            'name' => 'Rekayasa Perangkat Lunak',
            'description' => 'Jurusan utama',
        ], $overrides['departmentData'] ?? []),
        adminData: array_merge([
            'email' => 'admin@smkn1bdg.sch.id',
            'password' => 'StrongPass1',
        ], $overrides['adminData'] ?? []),
        stepsToComplete: $overrides['stepsToComplete'] ?? ['account', 'school', 'department'],
    );
}

/**
 * Mock the File facade so SaveRecoveryKeyAction performs no real IO.
 * SaveRecoveryKeyAction is final, so it cannot be mocked directly.
 */
function vejcxMockRecoveryKeySave(): void
{
    File::shouldReceive('exists')->andReturn(false);
    File::shouldReceive('makeDirectory')->andReturn(true);
    File::shouldReceive('put')->andReturn(1);
    File::shouldReceive('chmod')->andReturn(true);
}

describe('VEJCX: FinalizeSetupAction', function (): void {
    beforeEach(function (): void {
        SettingsService::clearOverrides();
        SettingsService::override(['setup.is_installed' => false]);
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');
        $this->mock(SendsNotifications::class, function (MockInterface $mock): void {
            $mock->shouldReceive('execute')->andReturnNull();
        });
    });

    test('VEJCX-FR-F1: finalization is atomic — creates school, department, admin, and settings together', function (): void {
        vejcxMockRecoveryKeySave();

        app(FinalizeSetupAction::class)->execute(vejcxFinalizeData());

        // School settings written
        expect(setting('school.name'))->toBe('SMK Negeri 1 Bandung');
        expect(setting('school.institutional_code'))->toBe('20212345');
        expect(setting('school.email'))->toBe('info@smkn1bdg.sch.id');

        // Department created
        $department = Department::where('name', 'Rekayasa Perangkat Lunak')->first();
        expect($department)->not->toBeNull();
        $this->assertModelExists($department);

        // Super admin created
        $admin = User::where('username', config('setup.defaults.admin_username', 'superadmin'))->first();
        expect($admin)->not->toBeNull();
        $this->assertModelExists($admin);
        expect($admin->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();
        expect($admin->status)->toBe(AccountStatus::PROTECTED);
        expect($admin->setup_required)->toBeFalse();
    });

    test('VEJCX-FR-F8: sets is_installed to true after finalization', function (): void {
        vejcxMockRecoveryKeySave();

        app(FinalizeSetupAction::class)->execute(vejcxFinalizeData());

        $this->assertDatabaseHas('settings', ['key' => 'setup.is_installed']);

        SettingsService::clearOverrides();
        expect(setting('setup.is_installed'))->toBeTrue();
    });

    test('VEJCX-FR-F9: saves brand_name and site_title from the school name', function (): void {
        vejcxMockRecoveryKeySave();

        app(FinalizeSetupAction::class)->execute(vejcxFinalizeData(['schoolData' => ['name' => 'SMK Bhakti Karya']]));

        expect(setting('brand_name'))->toBe('SMK Bhakti Karya');
        expect(setting('site_title'))->toContain('SMK Bhakti Karya');
    });

    test('VEJCX-FR-F10: dispatches the SetupFinalized event', function (): void {
        Event::fake([SetupFinalized::class]);
        vejcxMockRecoveryKeySave();

        app(FinalizeSetupAction::class)->execute(vejcxFinalizeData());

        Event::assertDispatched(SetupFinalized::class);
    });

    test('VEJCX-FR-F6: returns a 64-char recovery key and stores its hash in setup.install_recovery_key', function (): void {
        vejcxMockRecoveryKeySave();

        $plaintext = app(FinalizeSetupAction::class)->execute(vejcxFinalizeData());

        expect(strlen($plaintext))->toBe(64);
        expect(setting('setup.install_recovery_key'))->not->toBeNull();
        expect(Hash::check($plaintext, (string) setting('setup.install_recovery_key')))->toBeTrue();
    });

    test('VEJCX-FR-F7: writes the plaintext recovery key to the recovery key file', function (): void {
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('makeDirectory')->andReturn(true);
        File::shouldReceive('put')
            ->once()
            ->withArgs(fn (string $path, string $contents): bool => str_contains($path, '.recovery-key') && str_contains($contents, 'INTERNARA RECOVERY KEY'))
            ->andReturn(1);
        File::shouldReceive('chmod')->andReturn(true);

        $plaintext = app(FinalizeSetupAction::class)->execute(vejcxFinalizeData());

        expect(strlen($plaintext))->toBe(64);
    });

    test('VEJCX-NFR-R2: a recovery key file write failure does not block finalization', function (): void {
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('makeDirectory')->andReturn(true);
        File::shouldReceive('put')->andReturn(false); // signals a failed write
        File::shouldReceive('chmod')->andReturn(true);

        $plaintext = app(FinalizeSetupAction::class)->execute(vejcxFinalizeData());

        expect(strlen($plaintext))->toBe(64);

        $this->assertDatabaseHas('settings', ['key' => 'setup.is_installed']);
    });

    test('VEJCX-FR-F14: throws RejectedException when the system is already installed', function (): void {
        SettingsService::override(['setup.is_installed' => true]);

        expect(fn () => app(FinalizeSetupAction::class)->execute(vejcxFinalizeData()))
            ->toThrow(RejectedException::class);
    });
});
