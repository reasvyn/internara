<?php

declare(strict_types=1);

use App\Actions\User\SetupSuperAdminAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('creates a super admin user', function () {
        $admin = app(SetupSuperAdminAction::class)->execute([
            'name' => 'System Admin',
            'email' => 'sysadmin@school.edu',
            'password' => 'very-secure-password',
        ]);

        expect($admin)->toBeInstanceOf(User::class)
            ->and($admin->email)->toBe('sysadmin@school.edu')
            ->and($admin->hasRole('super_admin'))->toBeTrue()
            ->and($admin->hasVerifiedEmail())->toBeTrue();
    });

    it('throws validation error with missing fields', function () {
        expect(fn () => app(SetupSuperAdminAction::class)->execute([
            'name' => 'Admin',
        ]))->toThrow(ValidationException::class);
    });
});
