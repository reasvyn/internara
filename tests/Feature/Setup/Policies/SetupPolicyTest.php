<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Policies;

use App\Domain\Setup\Models\Setup;
use App\Domain\Setup\Policies\SetupPolicy;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
});

describe('SetupPolicy', function () {
    it('allows admin to viewAny', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        expect((new SetupPolicy)->viewAny($admin))->toBeTrue();
    });

    it('denies non-admin to viewAny', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        expect((new SetupPolicy)->viewAny($user))->toBeFalse();
    });

    it('allows admin to view', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $setup = Setup::factory()->create();

        expect((new SetupPolicy)->view($admin, $setup))->toBeTrue();
    });

    it('denies non-admin to view', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $setup = Setup::factory()->create();

        expect((new SetupPolicy)->view($user, $setup))->toBeFalse();
    });

    it('denies create for everyone', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $setup = Setup::factory()->create();

        expect((new SetupPolicy)->create($admin))->toBeFalse();
    });

    it('allows admin to update', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $setup = Setup::factory()->create();

        expect((new SetupPolicy)->update($admin, $setup))->toBeTrue();
    });

    it('denies non-admin to update', function () {
        $user = User::factory()->create();
        $user->assignRole('student');
        $setup = Setup::factory()->create();

        expect((new SetupPolicy)->update($user, $setup))->toBeFalse();
    });

    it('denies delete for everyone', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $setup = Setup::factory()->create();

        expect((new SetupPolicy)->delete($admin, $setup))->toBeFalse();
    });
});
