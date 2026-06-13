<?php

declare(strict_types=1);

use App\Journals\Logbook\Models\Logbook;
use App\Journals\Logbook\Policies\LogbookPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {});

function createLogbookPolicy(): LogbookPolicy
{
    return app(LogbookPolicy::class);
}

test('viewAny allows all roles', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect(createLogbookPolicy()->viewAny($user))->toBeTrue();
})->with(['super_admin', 'admin', 'teacher', 'supervisor', 'student']);

test('view allows admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $entry = Logbook::factory()->make();

    expect(createLogbookPolicy()->view($user, $entry))->toBeTrue();
});

test('view allows owner', function () {
    $user = User::factory()->create();
    $entry = Logbook::factory()->make(['user_id' => $user->id]);

    expect(createLogbookPolicy()->view($user, $entry))->toBeTrue();
});

test('view denies non-owner without role', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $entry = Logbook::factory()->make(['user_id' => $other->id]);

    expect(createLogbookPolicy()->view($user, $entry))->toBeFalse();
});

test('create only allows student', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect(createLogbookPolicy()->create($student))->toBeTrue();
    expect(createLogbookPolicy()->create($admin))->toBeFalse();
});

test('update allows admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $entry = Logbook::factory()->make();

    expect(createLogbookPolicy()->update($user, $entry))->toBeTrue();
});

test('update allows owner when not submitted', function () {
    $user = User::factory()->create();
    $entry = Logbook::factory()->make([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    expect(createLogbookPolicy()->update($user, $entry))->toBeTrue();
});

test('update denies owner when submitted', function () {
    $user = User::factory()->create();
    $entry = Logbook::factory()->make([
        'user_id' => $user->id,
        'status' => 'submitted',
    ]);

    expect(createLogbookPolicy()->update($user, $entry))->toBeFalse();
});

test('update denies non-owner', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $entry = Logbook::factory()->make(['user_id' => $other->id]);

    expect(createLogbookPolicy()->update($user, $entry))->toBeFalse();
});

test('delete allows admin', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $entry = Logbook::factory()->make();

    expect(createLogbookPolicy()->delete($user, $entry))->toBeTrue();
});

test('delete allows owner when not submitted', function () {
    $user = User::factory()->create();
    $entry = Logbook::factory()->make([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    expect(createLogbookPolicy()->delete($user, $entry))->toBeTrue();
});

test('delete denies owner when submitted', function () {
    $user = User::factory()->create();
    $entry = Logbook::factory()->make([
        'user_id' => $user->id,
        'status' => 'submitted',
    ]);

    expect(createLogbookPolicy()->delete($user, $entry))->toBeFalse();
});

test('delete denies non-owner', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $entry = Logbook::factory()->make(['user_id' => $other->id]);

    expect(createLogbookPolicy()->delete($user, $entry))->toBeFalse();
});

test('addSupervisorNote denies non-supervisor', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $entry = Logbook::factory()->make();

    expect(createLogbookPolicy()->addSupervisorNote($user, $entry))->toBeFalse();
});

test('addSupervisorNote denies supervisor without mentor relationship', function () {
    $user = User::factory()->create();
    $user->assignRole('supervisor');
    $entry = Logbook::factory()->make();

    expect(createLogbookPolicy()->addSupervisorNote($user, $entry))->toBeFalse();
});
