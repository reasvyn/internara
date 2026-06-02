<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use App\Domain\User\Policies\UserPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('UserPolicy', function () {
    it('allows super_admin to view any', function () {
        $user = User::factory()->create()->assignRole('super_admin');

        expect((new UserPolicy)->viewAny($user))->toBeTrue();
    });

    it('allows admin to view any', function () {
        $user = User::factory()->create()->assignRole('admin');

        expect((new UserPolicy)->viewAny($user))->toBeTrue();
    });

    it('denies student to view any', function () {
        $user = User::factory()->create()->assignRole('student');

        expect((new UserPolicy)->viewAny($user))->toBeFalse();
    });

    it('allows user to view themselves', function () {
        $user = User::factory()->create();
        test()->actingAs($user);

        expect((new UserPolicy)->view($user, $user))->toBeTrue();
    });

    it('allows super_admin to view other users', function () {
        $admin = User::factory()->create()->assignRole('super_admin');
        $other = User::factory()->create();

        expect((new UserPolicy)->view($admin, $other))->toBeTrue();
    });

    it('denies student to view other users', function () {
        $student = User::factory()->create()->assignRole('student');
        $other = User::factory()->create();

        expect((new UserPolicy)->view($student, $other))->toBeFalse();
    });

    it('allows super_admin to update themselves', function () {
        $user = User::factory()->create()->assignRole('super_admin');

        expect((new UserPolicy)->update($user, $user))->toBeTrue();
    });

    it('prevents admin from deleting super_admin', function () {
        $superAdmin = User::factory()->create()->assignRole('super_admin');
        $admin = User::factory()->create()->assignRole('admin');

        expect((new UserPolicy)->delete($admin, $superAdmin))->toBeFalse();
    });

    it('prevents user from deleting themselves', function () {
        $user = User::factory()->create();
        test()->actingAs($user);

        expect((new UserPolicy)->delete($user, $user))->toBeFalse();
    });

    it('allows super_admin to delete other users', function () {
        $admin = User::factory()->create()->assignRole('super_admin');
        $other = User::factory()->create();

        expect((new UserPolicy)->delete($admin, $other))->toBeTrue();
    });

    it('prevents restoring super_admin', function () {
        $superAdmin = User::factory()->create()->assignRole('super_admin');
        $admin = User::factory()->create()->assignRole('admin');

        expect((new UserPolicy)->restore($admin, $superAdmin))->toBeFalse();
    });
});
