<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Permissions\Enums\Role as RoleEnum;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Setup\Domain\SetupWizard\Actions\SetupSuperAdminAction;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

describe('VEJCX: SetupSuperAdminAction', function (): void {
    beforeEach(function (): void {
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value, 'web');
    });

    test('VEJCX-FR-W6: email is required', function (): void {
        expect(fn () => app(SetupSuperAdminAction::class)->execute('', 'StrongPass1'))
            ->toThrow(ValidationException::class);
    });

    test('VEJCX-FR-W6: email must be a valid email address', function (): void {
        expect(fn () => app(SetupSuperAdminAction::class)->execute('not-an-email', 'StrongPass1'))
            ->toThrow(ValidationException::class);
    });

    test('VEJCX-FR-F4: creates super admin with superadmin role, PROTECTED status, and verified email', function (): void {
        $user = app(SetupSuperAdminAction::class)->execute('admin@smkn1bdg.sch.id', 'StrongPass1');

        $this->assertModelExists($user);
        expect($user->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();
        expect($user->status)->toBe(AccountStatus::PROTECTED);
        expect($user->email_verified_at)->not->toBeNull();
    });

    test('VEJCX-FR-F5: super admin setup_required flag is set to false', function (): void {
        $user = app(SetupSuperAdminAction::class)->execute('admin@smkn1bdg.sch.id', 'StrongPass1');

        expect($user->fresh()->setup_required)->toBeFalse();
    });

    test('VEJCX-NFR-S6: rejects a weak password that fails Laravel Password rules', function (): void {
        expect(fn () => app(SetupSuperAdminAction::class)->execute('admin@smkn1bdg.sch.id', 'weak'))
            ->toThrow(ValidationException::class);
    });

    test('VEJCX-NFR-S7: super admin account is marked PROTECTED (non-deletable, non-lockable)', function (): void {
        $user = app(SetupSuperAdminAction::class)->execute('admin@smkn1bdg.sch.id', 'StrongPass1');

        expect($user->fresh()->status)->toBe(AccountStatus::PROTECTED);
        expect(AccountStatus::PROTECTED->isTerminal())->toBeTrue();
    });

    test('VEJCX-FR-F4: throws RejectedException when an immutable super admin already exists', function (): void {
        $existing = User::create([
            'name' => 'Existing Super',
            'username' => config('setup.defaults.admin_username', 'superadmin'),
            'email' => 'existing@smkn1bdg.sch.id',
            'password' => bcrypt('StrongExisting1'),
        ]);
        $existing->assignRole(RoleEnum::SUPER_ADMIN->value);
        $existing->setStatus(AccountStatus::PROTECTED);

        expect(fn () => app(SetupSuperAdminAction::class)->execute('new@smkn1bdg.sch.id', 'StrongPass1'))
            ->toThrow(RejectedException::class);
    });
});
