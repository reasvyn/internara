<?php

declare(strict_types=1);

use App\Auth\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {});

test('superadmin integrity rules detect valid name', function () {
    $user = User::factory()->create(['name' => 'Administrator']);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->isNameValid())->toBeTrue();
});

test('superadmin integrity rules detect invalid name', function () {
    $user = User::factory()->create(['name' => 'Not Administrator']);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->isNameValid())->toBeFalse();
});

test('superadmin integrity rules detect valid username', function () {
    $user = User::factory()->create(['username' => 'superadmin']);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->isUsernameValid())->toBeTrue();
});

test('superadmin integrity rules detect invalid username', function () {
    $user = User::factory()->create(['username' => 'not-superadmin']);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->isUsernameValid())->toBeFalse();
});

test('superadmin cannot be deleted', function () {
    $user = User::factory()->create(['name' => 'Administrator', 'username' => 'superadmin']);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->canBeDeleted())->toBeFalse();
});

test('regular user can be deleted', function () {
    $user = User::factory()->create();

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->canBeDeleted())->toBeTrue();
});

test('superadmin cannot be locked', function () {
    $user = User::factory()->create(['name' => 'Administrator', 'username' => 'superadmin']);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->canBeLocked())->toBeFalse();
});

test('superadmin cannot change name', function () {
    $user = User::factory()->create(['name' => 'Administrator', 'username' => 'superadmin']);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->canChangeName())->toBeFalse();
});

test('superadmin cannot change username', function () {
    $user = User::factory()->create(['name' => 'Administrator', 'username' => 'superadmin']);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->canChangeUsername())->toBeFalse();
});

test('superadmin must have protected status', function () {
    $user = User::factory()->create([
        'name' => 'Administrator',
        'username' => 'superadmin',
        'status' => 'protected',
    ]);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->hasProtectedStatus())->toBeTrue();
});

test('superadmin without protected status is detected', function () {
    $user = User::factory()->create([
        'name' => 'Administrator',
        'username' => 'superadmin',
        'status' => 'activated',
    ]);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->hasProtectedStatus())->toBeFalse();
});

test('superadmin with protected status is immutable', function () {
    $user = User::factory()->create([
        'name' => 'Administrator',
        'username' => 'superadmin',
        'status' => 'protected',
    ]);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->isImmutable())->toBeTrue();
});

test('superadmin without protected status is not immutable', function () {
    $user = User::factory()->create([
        'name' => 'Administrator',
        'username' => 'superadmin',
        'status' => 'activated',
    ]);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->isImmutable())->toBeFalse();
});

test('regular user is not immutable', function () {
    $user = User::factory()->create();

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->isImmutable())->toBeFalse();
});

test('superadmin is last superadmin when count is 1', function () {
    $user = User::factory()->create(['name' => 'Administrator', 'username' => 'superadmin']);
    $user->assignRole('superadmin');

    $rules = SuperAdminIntegrityRules::fromModel($user);

    expect($rules->isLastSuperAdmin())->toBeTrue();
});
