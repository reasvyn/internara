<?php

declare(strict_types=1);

use App\Auth\Permissions\Enums\Role;
use App\SysAdmin\Observability\Services\PulseGuard;
use App\User\Models\User;

test('view pulse returns true for super admin', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::SUPER_ADMIN->value);

    expect(PulseGuard::viewPulse($user))->toBeTrue();
});

test('view pulse returns true for admin', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::ADMIN->value);

    expect(PulseGuard::viewPulse($user))->toBeTrue();
});

test('view pulse returns false for student', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::STUDENT->value);

    expect(PulseGuard::viewPulse($user))->toBeFalse();
});

test('view pulse returns false for null user', function () {
    expect(PulseGuard::viewPulse(null))->toBeFalse();
});

test('view pulse returns false for unauthenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    expect(PulseGuard::viewPulse($user))->toBeFalse();
});
