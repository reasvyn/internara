<?php

declare(strict_types=1);

use App\Actions\Setup\InitializeSuperAdminAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('creates a super admin user', function () {
        $admin = app(InitializeSuperAdminAction::class)->execute(
            email: 'admin@school.edu',
            password: 'secure-password',
            name: 'Super Admin',
            username: 'superadmin',
        );

        expect($admin)->toBeInstanceOf(User::class)
            ->and($admin->email)->toBe('admin@school.edu')
            ->and($admin->hasRole('super_admin'))->toBeTrue()
            ->and($admin->profile)->not->toBeNull();
    });
});
