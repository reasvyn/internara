<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\Profile\Actions\ReadProfileFormAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {});

test('returns staff fields for super admin', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $action = app(ReadProfileFormAction::class);
    $data = $action->execute($user);

    expect($data['role'])->toBe('superadmin');
    expect($data['canChangeName'])->toBeFalse();
    expect($data['canChangeUsername'])->toBeFalse();
    expect($data['staffFields'])->toContain('employment_status', 'job_title');
});

test('returns staff fields for admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $action = app(ReadProfileFormAction::class);
    $data = $action->execute($user);

    expect($data['role'])->toBe('admin');
    expect($data['canChangeName'])->toBeTrue();
    expect($data['canChangeUsername'])->toBeTrue();
    expect($data['staffFields'])->not->toBeEmpty();
});

test('returns staff fields for teacher', function () {
    $user = User::factory()->create();
    $user->assignRole('teacher');

    $action = app(ReadProfileFormAction::class);
    $data = $action->execute($user);

    expect($data['role'])->toBe('teacher');
    expect($data['staffFields'])->not->toBeEmpty();
});

test('returns no staff fields for student', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $action = app(ReadProfileFormAction::class);
    $data = $action->execute($user);

    expect($data['role'])->toBe('student');
    expect($data['staffFields'])->toBeEmpty();
    expect($data['canChangeName'])->toBeTrue();
    expect($data['canChangeUsername'])->toBeTrue();
});

test('returns no staff fields for supervisor', function () {
    $user = User::factory()->create();
    $user->assignRole('supervisor');

    $action = app(ReadProfileFormAction::class);
    $data = $action->execute($user);

    expect($data['role'])->toBe('supervisor');
    expect($data['staffFields'])->toBeEmpty();
});

test('always includes common fields', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $action = app(ReadProfileFormAction::class);
    $data = $action->execute($user);

    expect($data['fields'])->toMatchArray(['name', 'email', 'phone', 'address', 'bio']);
});

test('returns unknown role when user has no role', function () {
    $user = User::factory()->create();

    $action = app(ReadProfileFormAction::class);
    $data = $action->execute($user);

    expect($data['role'])->toBe('unknown');
});
