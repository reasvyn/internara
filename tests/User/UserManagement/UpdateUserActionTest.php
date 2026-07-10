<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use App\User\UserManagement\Actions\UpdateUserAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->action = app(UpdateUserAction::class);
});

test('updates user basic fields', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $updated = $this->action->execute($user, ['name' => 'New Name']);

    expect($updated->name)->toBe('New Name');
});

test('updates user email with uniqueness validation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect(fn () => $this->action->execute($user, ['email' => $other->email]))
        ->toThrow(ValidationException::class);
});

test('updates user password when provided', function () {
    $user = User::factory()->create();

    $this->action->execute($user, ['password' => 'new-secret-123']);

    expect(Hash::check('new-secret-123', $user->fresh()->password))->toBeTrue();
});

test('does not change password when not provided', function () {
    $user = User::factory()->create(['password' => Hash::make('original')]);
    $originalHash = $user->password;

    $this->action->execute($user, ['name' => 'New Name']);

    expect($user->fresh()->password)->toBe($originalHash);
});

test('updates user profile data', function () {
    $user = User::factory()->create();
    $user->profile()->create(['phone' => 'old']);

    $this->action->execute($user, [], ['phone' => '1234567890', 'address' => 'New Address']);

    $user->refresh();
    expect($user->profile->phone)->toBe('1234567890');
    expect($user->profile->address)->toBe('New Address');
});

test('syncs user roles', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $this->action->execute($user, ['name' => 'Test'], null, ['admin']);

    expect($user->fresh()->hasRole('admin'))->toBeTrue();
    expect($user->fresh()->hasRole('student'))->toBeFalse();
});

test('rejects changing super admin name', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect(fn () => $this->action->execute($user, ['name' => 'Hacker']))
        ->toThrow(RejectedException::class, 'Cannot change super admin name.');
});

test('rejects changing super admin username', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect(fn () => $this->action->execute($user, ['username' => 'newadmin']))
        ->toThrow(RejectedException::class, 'Cannot change super admin username.');
});

test('validates name against reserved authoritative name rule', function () {
    $user = User::factory()->create();

    expect(fn () => $this->action->execute($user, ['name' => '']))
        ->toThrow(ValidationException::class);
});

test('validates username uniqueness', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect(fn () => $this->action->execute($user, ['username' => $other->username]))
        ->toThrow(ValidationException::class);
});

test('allows same username for same user', function () {
    $user = User::factory()->create();
    $originalUsername = $user->username;

    $updated = $this->action->execute($user, ['username' => $originalUsername]);

    expect($updated->fresh()->username)->toBe($originalUsername);
});
