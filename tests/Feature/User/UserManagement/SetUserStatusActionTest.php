<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\User\AccountStatus\Notifications\AccountStatusNotification;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use App\User\UserManagement\Actions\SetUserStatusAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->action = app(SetUserStatusAction::class);
});

test('sets user status to verified', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $result = $this->action->execute($user, AccountStatus::VERIFIED);

    expect($result->status)->toBe(AccountStatus::VERIFIED);
});

test('sets user status to suspended', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $result = $this->action->execute($user, AccountStatus::SUSPENDED);

    expect($result->status)->toBe(AccountStatus::SUSPENDED);
});

test('rejects changing your own status', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $this->actingAs($user);

    expect(fn () => $this->action->execute($user, AccountStatus::SUSPENDED))
        ->toThrow(RejectedException::class, 'Cannot change your own status.');
});

test('allows changing own status with skipAuthCheck', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $this->actingAs($user);

    $result = $this->action->execute($user, AccountStatus::VERIFIED, skipAuthCheck: true);

    expect($result->status)->toBe(AccountStatus::VERIFIED);
});

test('rejects invalid status transition', function () {
    $user = User::factory()->create(['status' => AccountStatus::PROVISIONED]);
    $user->assignRole('student');

    expect(fn () => $this->action->execute($user, AccountStatus::ARCHIVED))
        ->toThrow(RejectedException::class);
});

test('rejects changing super admin status', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect(fn () => $this->action->execute($user, AccountStatus::SUSPENDED))
        ->toThrow(RejectedException::class, 'Cannot change super admin account status.');
});

test('notifies user on status change', function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->assignRole('student');

    $this->action->execute($user, AccountStatus::SUSPENDED, 'Test reason');

    Notification::assertSentTo($user, AccountStatusNotification::class);
});

test('uses default reason when none provided', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $result = $this->action->execute($user, AccountStatus::VERIFIED);

    expect($result->status)->toBe(AccountStatus::VERIFIED);
});
