<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Policies\ProfilePolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('ProfilePolicy', function () {
    it('allows admin to view any', function () {
        $user = User::factory()->create()->assignRole('admin');

        expect((new ProfilePolicy)->viewAny($user))->toBeTrue();
    });

    it('denies student to view any', function () {
        $user = User::factory()->create()->assignRole('student');

        expect((new ProfilePolicy)->viewAny($user))->toBeFalse();
    });

    it('allows admin to view any profile', function () {
        $admin = User::factory()->create()->assignRole('admin');
        $profile = Profile::factory()->create();

        expect((new ProfilePolicy)->view($admin, $profile))->toBeTrue();
    });

    it('allows owner to view their own profile', function () {
        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create();
        test()->actingAs($user);

        expect((new ProfilePolicy)->view($user, $profile))->toBeTrue();
    });

    it('denies non-admin non-owner from viewing profile', function () {
        $owner = User::factory()->create();
        $profile = Profile::factory()->for($owner)->create();
        $other = User::factory()->create()->assignRole('student');

        expect((new ProfilePolicy)->view($other, $profile))->toBeFalse();
    });

    it('allows admin to update any profile', function () {
        $admin = User::factory()->create()->assignRole('admin');
        $profile = Profile::factory()->create();

        expect((new ProfilePolicy)->update($admin, $profile))->toBeTrue();
    });

    it('allows owner to update their own profile', function () {
        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create();
        test()->actingAs($user);

        expect((new ProfilePolicy)->update($user, $profile))->toBeTrue();
    });
});
