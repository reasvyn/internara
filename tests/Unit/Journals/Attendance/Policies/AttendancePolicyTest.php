<?php

declare(strict_types=1);

use App\Journals\Attendance\Models\Attendance;
use App\Journals\Attendance\Policies\AttendancePolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {});

function createAttendancePolicy(): AttendancePolicy
{
    return app(AttendancePolicy::class);
}

test('viewAny allows all roles', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect(createAttendancePolicy()->viewAny($user))->toBeTrue();
})->with(['super_admin', 'admin', 'teacher', 'supervisor', 'student']);

test('view allows admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $log = Attendance::factory()->make();

    expect(createAttendancePolicy()->view($user, $log))->toBeTrue();
});

test('view allows owner', function () {
    $user = User::factory()->create();
    $log = Attendance::factory()->make(['user_id' => $user->id]);

    expect(createAttendancePolicy()->view($user, $log))->toBeTrue();
});

test('view denies non-owner without role', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $log = Attendance::factory()->make(['user_id' => $other->id]);

    expect(createAttendancePolicy()->view($user, $log))->toBeFalse();
});

test('create only allows student', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect(createAttendancePolicy()->create($student))->toBeTrue();
    expect(createAttendancePolicy()->create($admin))->toBeFalse();
});

test('verify allows admin roles', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $log = Attendance::factory()->make();

    expect(createAttendancePolicy()->verify($user, $log))->toBeTrue();
})->with(['super_admin', 'admin', 'teacher']);

test('verify denies non-admin roles', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $log = Attendance::factory()->make();

    expect(createAttendancePolicy()->verify($user, $log))->toBeFalse();
})->with(['supervisor', 'student']);

test('update only allows admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $student = User::factory()->create();
    $student->assignRole('student');
    $log = Attendance::factory()->make();

    expect(createAttendancePolicy()->update($admin, $log))->toBeTrue();
    expect(createAttendancePolicy()->update($student, $log))->toBeFalse();
});

test('delete only allows admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $student = User::factory()->create();
    $student->assignRole('student');
    $log = Attendance::factory()->make();

    expect(createAttendancePolicy()->delete($admin, $log))->toBeTrue();
    expect(createAttendancePolicy()->delete($student, $log))->toBeFalse();
});
