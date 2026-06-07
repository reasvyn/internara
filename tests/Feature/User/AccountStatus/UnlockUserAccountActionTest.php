<?php

declare(strict_types=1);

use App\User\AccountStatus\Actions\UnlockUserAccountAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(UnlockUserAccountAction::class);
});

test('unlocks a locked user account', function () {
    $this->user->update(['locked_at' => now(), 'locked_reason' => 'too_many_failed_attempts']);

    $this->action->execute($this->user);

    expect($this->user->fresh()->locked_at)->toBeNull();
    expect($this->user->fresh()->locked_reason)->toBeNull();
});

test('does nothing when account is not locked', function () {
    $this->action->execute($this->user);

    expect($this->user->fresh()->locked_at)->toBeNull();
});

test('cannot unlock superadmin', function () {
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
    $this->user->assignRole('superadmin');

    expect(fn () => $this->action->execute($this->user))->toThrow(
        RuntimeException::class,
        'Super administrator accounts cannot be unlocked',
    );
});
