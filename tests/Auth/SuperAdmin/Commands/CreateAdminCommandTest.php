<?php

declare(strict_types=1);

use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {});

test('creates super admin with email and password arguments', function () {
    $this->artisan('admin:create', [
        'email' => 'admin@test.com',
        'password' => 'password123',
        '--no-interaction' => true,
    ])->assertSuccessful();

    $user = User::where('email', 'admin@test.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('super_admin'))->toBeTrue();
});

test('fails when super admin already exists', function () {
    $user = User::factory()->create(['email' => 'existing@test.com']);
    $user->assignRole('super_admin');

    $this->artisan('admin:create', [
        'email' => 'another@test.com',
        'password' => 'password123',
        '--no-interaction' => true,
    ])->assertFailed();
});

test('creates user with default name and username', function () {
    $this->artisan('admin:create', [
        'email' => 'admin@test.com',
        'password' => 'password123',
        '--no-interaction' => true,
    ])->assertSuccessful();

    $user = User::where('email', 'admin@test.com')->first();
    expect($user->name)->toBe(config('setup.defaults.admin_name', 'Administrator'));
    expect($user->username)->toBe(config('setup.defaults.admin_username', 'superadmin'));
});

test('sets correct account status after creation', function () {
    $this->artisan('admin:create', [
        'email' => 'admin@test.com',
        'password' => 'password123',
        '--no-interaction' => true,
    ])->assertSuccessful();

    $user = User::where('email', 'admin@test.com')->first();
    expect($user->status->value)->toBe('protected');
});
