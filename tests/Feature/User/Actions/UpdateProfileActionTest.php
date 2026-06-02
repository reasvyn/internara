<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\User\Actions\UpdateProfileAction;
use App\Domain\User\Enums\EmploymentStatus;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('UpdateProfileAction', function () {
    it('updates user profile data', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        $profile = app(UpdateProfileAction::class)->execute($user, [
            'phone' => '08123456789',
            'address' => 'Jl. Merdeka No. 1',
            'bio' => 'A test bio',
        ]);

        expect($profile)->toBeInstanceOf(Profile::class);
        expect($profile->phone)->toBe('08123456789');
        expect($profile->address)->toBe('Jl. Merdeka No. 1');
    });

    it('creates profile if not exists', function () {
        $user = User::factory()->create();
        expect($user->profile)->toBeNull();

        $profile = app(UpdateProfileAction::class)->execute($user, [
            'phone' => '08123456789',
        ]);

        expect($profile)->toBeInstanceOf(Profile::class);
        expect($profile->fresh()->phone)->toBe('08123456789');
    });

    it('updates user name when provided', function () {
        $user = User::factory()->create(['name' => 'Old Name']);
        $user->assignRole('student');

        app(UpdateProfileAction::class)->execute($user, [], name: 'New Name');

        expect($user->fresh()->name)->toBe('New Name');
    });

    it('updates user email when provided', function () {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $user->assignRole('student');

        app(UpdateProfileAction::class)->execute($user, [], email: 'new@example.com');

        expect($user->fresh()->email)->toBe('new@example.com');
    });

    it('updates staff profile with employment data', function () {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $profile = app(UpdateProfileAction::class)->execute($user, [
            'employment_status' => EmploymentStatus::FULL_TIME->value,
            'job_title' => 'Math Teacher',
        ]);

        expect($profile->employment_status)->toBe(EmploymentStatus::FULL_TIME);
        expect($profile->job_title)->toBe('Math Teacher');
    });

    it('cannot update super admin name', function () {
        $user = User::factory()->create(['name' => 'Administrator']);
        $user->assignRole('super_admin');

        expect(fn () => app(UpdateProfileAction::class)->execute(
            $user, [], name: 'Hacker'
        ))->toThrow(RejectedException::class);
    });

    it('validates profile data', function () {
        $user = User::factory()->create();
        $user->assignRole('student');

        expect(fn () => app(UpdateProfileAction::class)->execute(
            $user, ['phone' => str_repeat('x', 100)]
        ))->toThrow(ValidationException::class);
    });
});
