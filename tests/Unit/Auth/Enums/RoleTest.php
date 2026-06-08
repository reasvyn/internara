<?php

declare(strict_types=1);

use App\Auth\Permissions\Enums\Role;

test('role has label for each case', function () {
    foreach (Role::cases() as $case) {
        expect($case->label())->toBeString();
    }
});

test('role values are string backed', function () {
    expect(Role::SUPER_ADMIN->value)->toBe('superadmin');
    expect(Role::ADMIN->value)->toBe('admin');
    expect(Role::TEACHER->value)->toBe('teacher');
    expect(Role::STUDENT->value)->toBe('student');
    expect(Role::SUPERVISOR->value)->toBe('supervisor');
});

test('super admin value maps to spatie guard', function () {
    expect(Role::SUPER_ADMIN->value)->toBe('superadmin');
});

test('is user role returns true for user roles', function () {
    expect(Role::SUPER_ADMIN->isUserRole())->toBeTrue();
    expect(Role::STUDENT->isUserRole())->toBeTrue();
    expect(Role::MENTOR->isUserRole())->toBeFalse();
});

test('is functional role returns true for functional roles', function () {
    expect(Role::MENTOR->isFunctionalRole())->toBeTrue();
    expect(Role::ADMIN->isFunctionalRole())->toBeTrue();
    expect(Role::STUDENT->isFunctionalRole())->toBeFalse();
});

test('resolves to returns correct roles', function () {
    expect(Role::SUPER_ADMIN->resolvesTo())->toBe([Role::SUPER_ADMIN]);
    expect(Role::MENTOR->resolvesTo())->toBe([Role::TEACHER, Role::SUPERVISOR]);
    expect(Role::MENTEE->resolvesTo())->toBe([Role::STUDENT]);
});

test('is method checks resolved roles', function () {
    expect(Role::SUPER_ADMIN->is(Role::SUPER_ADMIN))->toBeTrue();
    expect(Role::TEACHER->is(Role::MENTOR))->toBeTrue();
    expect(Role::STUDENT->is(Role::MENTEE))->toBeTrue();
    expect(Role::TEACHER->is(Role::MENTEE))->toBeFalse();
});

test('user roles excludes super admin', function () {
    expect(Role::excludeSuperAdmin())->not->toContain(Role::SUPER_ADMIN);
    expect(Role::excludeSuperAdmin())->toHaveCount(4);
});

test('functional roles for returns correct roles', function () {
    expect(Role::functionalRolesFor(Role::TEACHER))->toBe([Role::MENTOR]);
    expect(Role::functionalRolesFor(Role::STUDENT))->toBe([Role::MENTEE]);
});