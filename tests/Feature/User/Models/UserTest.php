<?php

declare(strict_types=1);

use App\Enrollment\Models\Registration;
use App\User\Models\User;
use App\User\Profile\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

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

    expect(fn () => $user->delete())->toThrow(RuntimeException::class, 'Super administrator accounts cannot be deleted.');
});
