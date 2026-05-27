<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Setup\Actions\RecoverSuperAdminAction;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
});

describe('RecoverSuperAdminAction', function () {
    it('creates a new super admin user', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'recover@test.com',
            password: 'NewPass123',
        );

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue()
            ->and($user->email)->toBe('recover@test.com');
    });

    it('creates user with profile', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'withprofile@test.com',
            password: 'NewPass123',
        );

        expect($user->profile)->not->toBeNull();
    });

    it('resets existing super admin password', function () {
        $existing = User::factory()->create([
            'email' => 'existing@test.com',
            'password' => Hash::make('old-password'),
        ])->assignRole(Role::SUPER_ADMIN->value);
        $existing->setStatus(AccountStatus::PROTECTED);

        $result = app(RecoverSuperAdminAction::class)->execute(
            email: 'existing@test.com',
            password: 'NewPass123',
            isReset: true,
        );

        expect($result->id)->toBe($existing->id)
            ->and(Hash::check('NewPass123', $result->fresh()->password))->toBeTrue()
            ->and($result->fresh()->locked_at)->toBeNull();
    });

    it('returns user with super_admin role', function () {
        $user = app(RecoverSuperAdminAction::class)->execute(
            email: 'checkrole@test.com',
            password: 'NewPass123',
        );

        expect($user->hasRole(Role::SUPER_ADMIN->value))->toBeTrue();
    });
});

describe('RecoverAdminCommand', function () {
    it('registers the command', function () {
        $commands = Artisan::all();

        expect($commands)->toHaveKey('admin:recover');
    });

    it('fails without valid recovery key', function () {
        $this->artisan('admin:recover', ['email' => 'admin@test.com'])
            ->assertExitCode(1);
    });

    it('fails when email already exists without --reset flag', function () {
        Setup::truncate();
        Setup::create([
            'is_installed' => true,
            'completed_steps' => [],
            'recovery_key' => Hash::make('valid-key'),
        ]);
        storage_path('app/private/.recovery-key');

        $existing = User::factory()->create([
            'email' => 'existing@test.com',
        ])->assignRole(Role::SUPER_ADMIN->value);

        $this->artisan('admin:recover', ['email' => 'existing@test.com', '--key' => 'valid-key'])
            ->assertExitCode(1);
    });
});
