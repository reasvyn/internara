<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\User\AccountStatus\Notifications\AccountStatusNotification;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use App\User\UserManagement\Actions\ToggleUserStatusAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'student']);
    $this->action = app(ToggleUserStatusAction::class);
});

test('toggles verified user to suspended', function () {
    $user = User::factory()->create(['status' => AccountStatus::VERIFIED]);
    $user->assignRole('student');

    $result = $this->action->execute($user);

    expect($result->status)->toBe(AccountStatus::SUSPENDED);
});

test('toggles suspended user to verified', function () {
    $user = User::factory()->create(['status' => AccountStatus::SUSPENDED]);
    $user->assignRole('student');

    $result = $this->action->execute($user);

    expect($result->status)->toBe(AccountStatus::VERIFIED);
});

test('toggles provisioned user to verified', function () {
    $user = User::factory()->create(['status' => AccountStatus::PROVISIONED]);
    $user->assignRole('student');

    $result = $this->action->execute($user);

    expect($result->status)->toBe(AccountStatus::VERIFIED);
});

test('rejects toggling your own status', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $this->actingAs($user);

    expect(fn () => $this->action->execute($user))
        ->toThrow(RuntimeException::class, 'Cannot change your own status.');
});

test('rejects toggling super admin status', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect(fn () => $this->action->execute($user))
        ->toThrow(RejectedException::class, 'Cannot toggle super admin account status.');
});

test('notifies user on toggle', function () {
    Notification::fake();
    $user = User::factory()->create(['status' => AccountStatus::VERIFIED]);
    $user->assignRole('student');

    $this->action->execute($user);

    Notification::assertSentTo($user, AccountStatusNotification::class);
});
