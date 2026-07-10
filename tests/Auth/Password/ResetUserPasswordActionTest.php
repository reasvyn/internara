<?php

declare(strict_types=1);

use App\Auth\Password\Actions\ResetUserPasswordAction;
use App\Core\Exceptions\RejectedException;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {

    $this->action = app(ResetUserPasswordAction::class);
});

test('resets password for regular user', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $result = $this->action->execute($user);

    expect($result->data)->toHaveKeys(['user', 'new_password']);
    expect($result->data['user']->id)->toBe($user->id);
    expect($result->data['new_password'])->toBeString();
    expect(strlen($result->data['new_password']))->toBe(12);
});

test('new password is hashed correctly', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $result = $this->action->execute($user);

    $user->refresh();
    expect(Hash::check($result->data['new_password'], $user->password))->toBeTrue();
});

test('rejects reset for super admin', function () {
    $user = User::factory()->create([
        'name' => 'Administrator',
        'username' => 'superadmin',
    ]);
    $user->assignRole('super_admin');
    $user->setStatus(AccountStatus::PROTECTED);

    expect(fn () => $this->action->execute($user))
        ->toThrow(RejectedException::class, 'Cannot reset super admin password through this interface');
});

test('generates different passwords on each reset', function () {
    $user = User::factory()->create();

    $result1 = $this->action->execute($user);
    $result2 = $this->action->execute($user);

    expect($result1->data['new_password'])->not->toBe($result2->data['new_password']);
});

test('password reset changes actual password in database', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);
    $user->assignRole('student');

    $oldHash = $user->password;
    $this->action->execute($user);

    $user->refresh();
    expect($user->password)->not->toBe($oldHash);
});
