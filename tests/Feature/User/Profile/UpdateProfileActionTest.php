<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\User\Aggregates\Profile\Actions\UpdateProfileAction;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('user can update their name, email, and username', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'username' => 'johndoe',
    ]);
    $user->assignRole('student');

    $action = app(UpdateProfileAction::class);

    $profile = $action->execute(
        user: $user,
        data: [
            'phone' => '1234567890',
            'address' => '123 Main St',
        ],
        name: 'John Updated',
        email: 'johnupdated@example.com',
        username: 'johnupdated',
    );

    $user->refresh();

    expect($user->name)->toBe('John Updated');
    expect($user->email)->toBe('johnupdated@example.com');
    expect($user->username)->toBe('johnupdated');
    expect($profile->phone)->toBe('1234567890');
    expect($profile->address)->toBe('123 Main St');
});

test('cannot update username of super admin', function () {
    $superAdmin = User::factory()->create([
        'name' => 'Administrator',
        'username' => 'superadmin',
        'email' => 'admin@example.com',
    ]);
    $superAdmin->assignRole('super_admin');

    $action = app(UpdateProfileAction::class);

    expect(fn () => $action->execute(
        user: $superAdmin,
        data: [],
        username: 'newusername'
    ))->toThrow(RejectedException::class, 'Cannot change super admin username.');
});

test('cannot update name of super admin', function () {
    $superAdmin = User::factory()->create([
        'name' => 'Administrator',
        'username' => 'superadmin',
        'email' => 'admin@example.com',
    ]);
    $superAdmin->assignRole('super_admin');

    $action = app(UpdateProfileAction::class);

    expect(fn () => $action->execute(
        user: $superAdmin,
        data: [],
        name: 'New Administrator Name'
    ))->toThrow(RejectedException::class, 'Cannot change super admin name.');
});

test('validation fails when username or email is not unique', function () {
    $user1 = User::factory()->create([
        'email' => 'user1@example.com',
        'username' => 'user1',
    ]);
    $user1->assignRole('student');

    $user2 = User::factory()->create([
        'email' => 'user2@example.com',
        'username' => 'user2',
    ]);
    $user2->assignRole('student');

    $action = app(UpdateProfileAction::class);

    // Try to update user2's username to user1's username
    expect(fn () => $action->execute(
        user: $user2,
        data: [],
        username: 'user1'
    ))->toThrow(ValidationException::class);
});
