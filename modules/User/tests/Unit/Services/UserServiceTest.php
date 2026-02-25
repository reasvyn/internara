<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Permission\Models\Role;
use Modules\Profile\Models\Profile;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

test('it fulfills [SYRS-NF-504] by creating user and profile atomically via createWithProfile', function () {
    // Arrange
    $service = app(UserService::class);
    $userData = [
        'name' => 'Atom Student',
        'email' => 'atom@example.com',
        'roles' => ['student'],
    ];
    $profileData = [
        'phone' => '08123456789',
    ];

    // Act
    $user = $service->createWithProfile($userData, $profileData);

    // Assert
    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBe('atom@example.com');
    
    $profile = app(ProfileService::class)->getByUserId($user->id);
    expect($profile->phone)->toBe('08123456789');
    expect($user->hasRole('student'))->toBeTrue();
});

test('it rolls back user creation if profile creation fails (atomicity check)', function () {
    // Arrange
    $service = app(UserService::class);
    $userData = [
        'name' => 'Failing Student',
        'email' => 'fail@example.com',
        'roles' => ['student'],
    ];

    // We force a failure in ProfileService by mocking it
    $profileService = $this->mock(ProfileService::class);
    $profileService->shouldReceive('getByUserId')->andThrow(new \Exception('Profile failure'));

    // Re-bind to use the mock
    $this->app->instance(ProfileService::class, $profileService);

    // Act & Assert
    expect(fn() => $service->createWithProfile($userData, ['phone' => '123']))
        ->toThrow(\Exception::class, 'Profile failure');

    // User should NOT exist in database due to rollback
    $this->assertDatabaseMissing('users', ['email' => 'fail@example.com']);
});
