<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Domain\UserManagement\Actions\CreateUserAction;
use App\Modules\User\Domain\UserManagement\Actions\DeleteUserAction;
use App\Modules\User\Domain\UserManagement\Actions\SetUserStatusAction;
use App\Modules\User\Domain\UserManagement\Actions\UpdateUserAction;
use App\Modules\User\Domain\UserManagement\Data\CreateUserData;
use App\Modules\User\Domain\UserManagement\Data\SetUserStatusData;
use App\Modules\User\Domain\UserManagement\Data\UpdateUserData;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('95EVB: user CRUD actions', function (): void {
    test('95EVB-FR-USER-002: create derives the username from the email when none is given', function (): void {
        $result = app(CreateUserAction::class)->execute(new CreateUserData(
            user: [
                'name' => 'Derived Name User',
                'email' => 'derived.name.user@example.com',
            ],
            sendNotification: false,
        ));

        expect($result)->toBeInstanceOf(User::class)
            ->and($result->username)->not->toBeEmpty();

        $this->assertModelExists($result);
        $this->assertDatabaseHas('users', ['email' => 'derived.name.user@example.com']);
    });

    test('95EVB-FR-USER-004: create persists the user and mints an activation token', function (): void {
        $result = app(CreateUserAction::class)->execute(new CreateUserData(
            user: [
                'name' => 'Token Mint User',
                'username' => 'tokenmintuser',
                'email' => 'token.mint.user@example.com',
                'password' => 'chosen-secret-1',
            ],
            sendNotification: false,
        ));

        $this->assertDatabaseHas('access_tokens', [
            'user_id' => $result->id,
            'token_type' => 'activation',
        ]);
    });

    test('95EVB-FR-USER-001: create rejects a duplicate email with ValidationException', function (): void {
        $existing = User::factory()->create();

        expect(fn () => app(CreateUserAction::class)->execute(new CreateUserData(
            user: [
                'name' => 'Duplicate Email User',
                'username' => 'uniqueusername1',
                'email' => $existing->email,
            ],
            sendNotification: false,
        )))->toThrow(ValidationException::class);
    });

    test('95EVB-FR-USER-007: update renames the user and persists the change', function (): void {
        $user = User::factory()->create();

        $result = app(UpdateUserAction::class)->execute(new UpdateUserData(
            userId: $user->id,
            user: ['name' => 'Renamed Through Action'],
        ));

        expect($result->id)->toBe($user->id);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Renamed Through Action',
        ]);
    });

    test('95EVB-FR-USER-008: update refuses a superadmin name change with RejectedException', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        expect(fn () => app(UpdateUserAction::class)->execute(new UpdateUserData(
            userId: $admin->id,
            user: ['name' => 'Someone Else Entirely'],
        )))->toThrow(RejectedException::class);

        expect($admin->fresh()->name)->toBe($admin->name);
    });

    test('95EVB-FR-USER-009: delete removes a regular user', function (): void {
        $user = User::factory()->create();

        app(DeleteUserAction::class)->execute($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    test('95EVB-FR-USER-009: delete refuses the superadmin with RejectedException', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        expect(fn () => app(DeleteUserAction::class)->execute($admin))
            ->toThrow(RejectedException::class);

        $this->assertModelExists($admin->fresh());
    });

    test('95EVB-FR-USER-009: delete refuses the acting user own account with RejectedException', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        expect(fn () => app(DeleteUserAction::class)->execute($user))
            ->toThrow(RejectedException::class);

        $this->assertModelExists($user->fresh());
    });

    test('95EVB-FR-USER-020: set-status applies a valid transition to the row', function (): void {
        $user = User::factory()->create(['status' => 'activated']);

        $result = app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $user->id,
            newStatus: AccountStatus::VERIFIED,
            reason: 'verified by test',
            skipAuthCheck: true,
        ));

        expect($result->fresh()->status)->toBe(AccountStatus::VERIFIED);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => AccountStatus::VERIFIED->value,
        ]);
    });

    test('95EVB-FR-USER-020: set-status rejects a transition out of a terminal state', function (): void {
        $user = User::factory()->create(['status' => 'archived']);

        expect(fn () => app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $user->id,
            newStatus: AccountStatus::VERIFIED,
            skipAuthCheck: true,
        )))->toThrow(RejectedException::class);

        expect($user->fresh()->status)->toBe(AccountStatus::ARCHIVED);
    });

    test('95EVB-FR-USER-021: set-status refuses self-change with RejectedException', function (): void {
        $user = User::factory()->create(['status' => 'verified']);
        $this->actingAs($user);

        expect(fn () => app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $user->id,
            newStatus: AccountStatus::SUSPENDED,
        )))->toThrow(RejectedException::class);

        expect($user->fresh()->status)->toBe(AccountStatus::VERIFIED);
    });

    test('95EVB-FR-USER-021: set-status refuses any change on the superadmin', function (): void {
        $admin = User::factory()->create(['status' => 'activated']);
        $admin->assignRole('super_admin');

        expect(fn () => app(SetUserStatusAction::class)->execute(new SetUserStatusData(
            userId: $admin->id,
            newStatus: AccountStatus::VERIFIED,
            skipAuthCheck: true,
        )))->toThrow(RejectedException::class);

        expect($admin->fresh()->status)->toBe(AccountStatus::ACTIVATED);
    });
});
