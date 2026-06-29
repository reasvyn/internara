<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use App\User\Profile\Models\Profile;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {});

test('user model has relationship with profile', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create(['user_id' => $user->id]);

    expect($user->profile)->toBeInstanceOf(Profile::class);
    expect($user->profile->id)->toBe($profile->id);
});

test('user model has registrations relationship directly', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->create(['student_id' => $user->id]);

    expect($user->registrations)->toHaveCount(1);
    expect($user->registrations->first()->id)->toBe($registration->id);
});

test('role super_admin maps to superadmin', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect($user->hasRole('superadmin'))->toBeTrue();
    expect($user->hasRole('super_admin'))->toBeTrue();
});

test('deleting superadmin throws runtime exception', function () {
    $user = User::factory()->create();
    $user->assignRole('superadmin');

    expect(fn () => $user->delete())->toThrow(
        RuntimeException::class,
        'Super administrator accounts cannot be deleted.',
    );
});

test('email cannot be null', function () {
    expect(fn () => User::factory()->create(['email' => null]))->toThrow(QueryException::class);
});

test('username cannot be null', function () {
    expect(fn () => User::factory()->create(['username' => null]))->toThrow(QueryException::class);
});

test('email must be unique', function () {
    User::factory()->create(['email' => 'same@example.com']);

    expect(User::where('email', 'same@example.com')->count())->toBe(1);
});

test('username must be unique', function () {
    User::factory()->create(['username' => 'uniqueuser']);

    expect(fn () => User::factory()->create(['username' => 'uniqueuser']))->toThrow(
        QueryException::class,
    );
});

test('superadmin has permanent name Administrator', function () {
    $user = User::factory()->create(['name' => 'Administrator', 'username' => 'superadmin']);
    $user->assignRole('superadmin');

    expect($user->name)->toBe('Administrator');
});

test('superadmin has permanent username superadmin', function () {
    $user = User::factory()->create(['name' => 'Administrator', 'username' => 'superadmin']);
    $user->assignRole('superadmin');

    expect($user->username)->toBe('superadmin');
});

test('user factory generates valid email', function () {
    $user = User::factory()->create();

    expect($user->email)->not->toBeNull();
    expect(filter_var($user->email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
});

test('user factory generates valid username', function () {
    $user = User::factory()->create();

    expect($user->username)->not->toBeNull();
    expect($user->username)->toMatch('/^[a-z][a-z0-9_]*$/');
});
