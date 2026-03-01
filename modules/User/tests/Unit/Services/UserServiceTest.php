<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit\Services;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permission\Models\Role;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $this->actingAs($admin);
});

test(
    'it fulfills [SYRS-NF-504] by creating user and profile atomically via createWithProfile',
    function () {
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
    },
);

test('it rolls back user creation if profile creation fails (atomicity check)', function () {
    // Arrange
    $userData = [
        'name' => 'Failing Student',
        'email' => 'fail@example.com',
        'roles' => ['student'],
    ];

    // We force a failure in ProfileService by mocking it
    $profileService = $this->mock(\Modules\Profile\Services\Contracts\ProfileService::class);
    $profileService->shouldReceive('getByUserId')->andReturn(new \Modules\Profile\Models\Profile);
    $profileService->shouldReceive('update')->andThrow(new \Exception('Profile failure'));

    // Resolve service AFTER mocking dependencies
    $service = app(UserService::class);

    // Act & Assert
    expect(fn () => $service->createWithProfile($userData, ['phone' => '123']))->toThrow(
        \Exception::class,
        'Profile failure',
    );

    // User should NOT exist in database due to rollback
    $this->assertDatabaseMissing('users', ['email' => 'fail@example.com']);
});

test('it enforces authorization on account creation', function () {
    // 1. Create a regular student user (unauthorized to create others)
    $student = User::factory()->create();
    $student->assignRole('student');

    $this->actingAs($student);
    $service = app(UserService::class);

    // 2. Act & Assert
    expect(fn () => $service->createWithProfile([], []))->toThrow(
        \Illuminate\Auth\Access\AuthorizationException::class,
    );
});

test('it records activity log when a user is created', function () {
    $service = app(UserService::class);

    $user = $service->createWithProfile(
        [
            'name' => 'Log Test',
            'email' => 'log@example.com',
            'roles' => ['student'],
        ],
        ['phone' => '123'],
    );

    $this->assertDatabaseHas('activities', [
        'subject_id' => $user->id,
        'description' => 'created',
    ]);
});
