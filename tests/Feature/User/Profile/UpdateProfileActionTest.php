<?php

declare(strict_types=1);

use App\Exceptions\RejectedException;
use App\User\Models\User;
use App\User\Profile\Actions\UpdateProfileAction;
use App\User\Profile\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(UpdateProfileAction::class);
});

test('updates profile with valid data', function () {
    $profile = $this->action->execute($this->user, [
        'phone' => '08123456789',
        'address' => 'Jl. Merdeka No. 1',
    ]);

    expect($profile)->toBeInstanceOf(Profile::class);
    expect($profile->phone)->toBe('08123456789');
    expect($profile->address)->toBe('Jl. Merdeka No. 1');
});

test('updates user name', function () {
    $profile = $this->action->execute($this->user, [], name: 'New Name');

    expect($this->user->fresh()->name)->toBe('New Name');
    expect($profile)->toBeInstanceOf(Profile::class);
});

test('updates user email', function () {
    $profile = $this->action->execute($this->user, [], email: 'newemail@test.com');

    expect($this->user->fresh()->email)->toBe('newemail@test.com');
});

test('cannot change superadmin name', function () {
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
    $this->user->assignRole('superadmin');
    $this->user->update(['name' => 'Administrator', 'username' => 'superadmin']);

    expect(fn () => $this->action->execute($this->user, [], name: 'New Admin'))
        ->toThrow(RejectedException::class, 'Cannot change super admin name.');
});

test('cannot change superadmin username', function () {
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
    $this->user->assignRole('superadmin');
    $this->user->update(['name' => 'Administrator', 'username' => 'superadmin']);

    expect(fn () => $this->action->execute($this->user, [], username: 'newadmin'))
        ->toThrow(RejectedException::class, 'Cannot change super admin username.');
});

test('creates profile if none exists', function () {
    expect($this->user->profile)->toBeNull();

    $profile = $this->action->execute($this->user, ['phone' => '08123456789']);

    expect($profile)->toBeInstanceOf(Profile::class);
    expect($this->user->fresh()->profile->phone)->toBe('08123456789');
});

test('updates existing profile', function () {
    Profile::factory()->create([
        'user_id' => $this->user->id,
        'phone' => '0811111111',
    ]);

    $profile = $this->action->execute($this->user, ['phone' => '0899999999']);

    expect($profile->phone)->toBe('0899999999');
});

test('validates profile data', function () {
    expect(fn () => $this->action->execute($this->user, ['phone' => str_repeat('1', 21)]))
        ->toThrow(ValidationException::class);
});
