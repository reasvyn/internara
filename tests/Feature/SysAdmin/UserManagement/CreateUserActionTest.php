<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\UserManagement;

use App\SysAdmin\UserManagement\Actions\CreateUserAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('create user action successfully creates user with profile and role', function () {
    // Setup role
    Role::create(['name' => 'admin']);

    $action = new CreateUserAction;

    $userData = [
        'name' => 'System Tester',
        'email' => 'tester@internara.dev',
        'username' => 'systester',
        'password' => 'secret123456',
        'setup_required' => false,
    ];

    $profileData = [
        'phone' => '1234567890',
        'address' => '123 Testing Ave',
    ];

    $user = $action->execute($userData, $profileData, ['admin'], false);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('System Tester');
    expect($user->email)->toBe('tester@internara.dev');
    expect($user->username)->toBe('systester');
    expect($user->hasRole('admin'))->toBeTrue();

    // Verify profile creation
    $profile = $user->profile;
    expect($profile)->not->toBeNull();
    expect($profile->phone)->toBe('1234567890');
    expect($profile->address)->toBe('123 Testing Ave');
});
