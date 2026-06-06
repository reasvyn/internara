<?php

declare(strict_types=1);

use App\User\AccountStatus\Actions\LockUserAccountAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(LockUserAccountAction::class);
});

test('locks a user account', function () {
    $this->action->execute($this->user);

    expect($this->user->fresh()->locked_at)->not->toBeNull();
    expect($this->user->fresh()->locked_reason)->toBe('too_many_failed_attempts');
});

test('locks with custom reason', function () {
    $this->action->execute($this->user, 'admin_action');

    expect($this->user->fresh()->locked_reason)->toBe('admin_action');
});

test('does not lock already locked account', function () {
    $this->user->update(['locked_at' => now(), 'locked_reason' => 'first_lock']);

    $this->action->execute($this->user, 'second_attempt');

    expect($this->user->fresh()->locked_reason)->toBe('first_lock');
});

test('cannot lock superadmin', function () {
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
    $this->user->assignRole('superadmin');

    expect(fn () => $this->action->execute($this->user))
        ->toThrow(RuntimeException::class, 'Super administrator accounts cannot be locked.');
});
