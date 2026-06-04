<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed Spatie roles
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('admin:create command creates a superadmin account when none exists', function () {
    // Ensure no superadmin exists
    expect(User::role('superadmin')->exists())->toBeFalse();

    $this->artisan('admin:create admin@example.com password123')
        ->assertSuccessful();

    // Verify user exists and has superadmin role
    $user = User::where('email', 'admin@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('superadmin'))->toBeTrue();
});

test('admin:create command fails if a superadmin already exists', function () {
    // Create a superadmin user first
    $user = User::factory()->create([
        'email' => 'existing-super@example.com',
        'username' => 'existingadmin',
    ]);
    $user->assignRole('superadmin');

    expect(User::role('superadmin')->exists())->toBeTrue();

    $this->artisan('admin:create newadmin@example.com password123')
        ->expectsOutputToContain(__('sysadmin.create.already_exists'))
        ->assertFailed();
});
