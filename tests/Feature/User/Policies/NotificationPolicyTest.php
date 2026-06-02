<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\Notification;
use App\Domain\User\Models\User;
use App\Domain\User\Policies\NotificationPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('NotificationPolicy', function () {
    it('allows any user to viewAny', function () {
        $user = User::factory()->create();

        expect((new NotificationPolicy)->viewAny($user))->toBeTrue();
    });

    it('allows owner to view their notification', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        test()->actingAs($user);

        expect((new NotificationPolicy)->view($user, $notification))->toBeTrue();
    });

    it('denies non-owner from viewing notification', function () {
        $owner = User::factory()->create();
        $notification = Notification::factory()->for($owner)->create();
        $other = User::factory()->create()->assignRole('student');

        expect((new NotificationPolicy)->view($other, $notification))->toBeFalse();
    });

    it('allows admin to create', function () {
        $user = User::factory()->create()->assignRole('admin');

        expect((new NotificationPolicy)->create($user))->toBeTrue();
    });

    it('denies student to create', function () {
        $user = User::factory()->create()->assignRole('student');

        expect((new NotificationPolicy)->create($user))->toBeFalse();
    });

    it('allows owner to update their notification', function () {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        test()->actingAs($user);

        expect((new NotificationPolicy)->update($user, $notification))->toBeTrue();
    });

    it('denies non-owner from updating notification', function () {
        $owner = User::factory()->create();
        $notification = Notification::factory()->for($owner)->create();
        $other = User::factory()->create()->assignRole('student');

        expect((new NotificationPolicy)->update($other, $notification))->toBeFalse();
    });

    it('allows admin to delete', function () {
        $user = User::factory()->create()->assignRole('admin');
        $notification = Notification::factory()->create();

        expect((new NotificationPolicy)->delete($user, $notification))->toBeTrue();
    });

    it('denies student to delete', function () {
        $user = User::factory()->create()->assignRole('student');
        $notification = Notification::factory()->create();

        expect((new NotificationPolicy)->delete($user, $notification))->toBeFalse();
    });
});
