<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Permissions\Enums\Role;
use App\Modules\Auth\Domain\SuperAdmin\Actions\RecoverSuperAdminAction;
use App\Modules\Auth\Domain\SuperAdmin\Events\SuperAdminRecovered;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Cache::clear(); // Ensure a clean cache for rate limiting tests

    // Ensure roles are seeded for permission checks
    // This might be done in a test migration or a dedicated test setup.
    // For now, manually create the role if it doesn't exist.
    if (! \Spatie\Permission\Models\Role::where('name', Role::SUPER_ADMIN->value)->exists()) {
        \Spatie\Permission\Models\Role::create(['name' => Role::SUPER_ADMIN->value]);
    }
});

test('C9ZB6-FR-P1: successfully recovers a super admin account with a new password', function () {
    // Arrange
    $oldPassword = 'old-secret';
    $newPassword = 'new-secret';
    $user = User::factory()->create(['email' => 'superadmin@example.com', 'password' => Hash::make($oldPassword)]);
    $user->assignRole(Role::SUPER_ADMIN->value);

    // Mock integrity rules if needed, or ensure user is setup correctly
    // For this test, we assume a valid super admin in a recoverable state.
    // We are not testing the asSuperAdminIntegrityRules method directly here, but its interaction.
    // If that method has complex logic, it should have its own unit tests.
    $userMock = Mockery::mock(User::class)->makePartial();
    $userMock->shouldReceive('where')->andReturnSelf();
    $userMock->shouldReceive('firstOrFail')->andReturn($user);
    $userMock->shouldReceive('asSuperAdminIntegrityRules->hasProtectedStatus')->andReturnTrue();
    $userMock->shouldReceive('hasRole')->andReturnTrue();
    $userMock->shouldReceive('update')->andReturnTrue();
    $userMock->shouldReceive('syncRoles')->andReturnTrue();
    $userMock->shouldReceive('forceFill->save')->andReturnTrue();
    app()->instance(User::class, $userMock);

    $action = app(RecoverSuperAdminAction::class);

    // Act
    $recoveredUser = $action->execute('superadmin@example.com', $newPassword);

    // Assert
    expect($recoveredUser->email)->toBe('superadmin@example.com');
    expect(Hash::check($newPassword, $recoveredUser->password))->toBeTrue();
    expect($recoveredUser->locked_at)->toBeNull();
    expect($recoveredUser->locked_reason)->toBeNull();
    Event::assertDispatched(SuperAdminRecovered::class);
    Event::assertDispatched('eloquent.created: App\Modules\Core\Models\AuditLog', function ($event, $log) {
        return $log->event === 'super_admin_recovered';
    });
    expect(Cache::has(config('cache-keys.recover_admin_attempts').md5('superadmin@example.com')))->toBeFalse();
});

test('C9ZB6-FR-RL1: throws RejectedException after too many failed recovery attempts', function () {
    // Arrange
    $email = 'superadmin@example.com';
    $password = 'anypassword';
    User::factory()->create(['email' => $email]); // User must exist for rate limiting to be hit after `firstOrFail`
    $cacheKey = config('cache-keys.recover_admin_attempts').md5($email);

    // Simulate 3 failed attempts
    Cache::put($cacheKey, 3, 900);

    $action = app(RecoverSuperAdminAction::class);

    // Act & Assert
    $this->expectException(RejectedException::class);
    $this->expectExceptionMessage(__( 'auth.recovery_throttle'));
    $action->execute($email, $password);

    // Assert cache attempt count remains 3 or increases if execution reaches that point
    expect(Cache::get($cacheKey))->toBe(3); // Should not increase beyond 3 if exception is thrown at the start
});

test('C9ZB6-FR-P2: increments failed attempt count on user not found or integrity violation', function () {
    // Arrange
    $email = 'nonexistent@example.com';
    $password = 'anypassword';
    $cacheKey = config('cache-keys.recover_admin_attempts').md5($email);

    $action = app(RecoverSuperAdminAction::class);

    // Act & Assert (User not found path)
    try {
        $action->execute($email, $password);
    } catch (Throwable $e) {
        // Expected exception
    }
    expect(Cache::get($cacheKey))->toBe(1);

    // Act & Assert (Integrity violation path - setup a user but with integrity fail)
    $email2 = 'integrityfail@example.com';
    $user2 = User::factory()->create(['email' => $email2]);
    $user2->assignRole(Role::SUPER_ADMIN->value);

    $userMock2 = Mockery::mock(User::class)->makePartial();
    $userMock2->shouldReceive('where')->andReturnSelf();
    $userMock2->shouldReceive('firstOrFail')->andReturn($user2);
    $userMock2->shouldReceive('asSuperAdminIntegrityRules->hasProtectedStatus')->andReturnFalse(); // Integrity fails
    $userMock2->shouldReceive('hasRole')->andReturnTrue();
    app()->instance(User::class, $userMock2);

    $cacheKey2 = config('cache-keys.recover_admin_attempts').md5($email2);

    try {
        $action->execute($email2, $password);
    } catch (Throwable $e) {
        // Expected exception
    }
    expect(Cache::get($cacheKey2))->toBe(1);
});

test('C9ZB6-FR-P3: throws RejectedException if super admin integrity rules are violated', function () {
    // Arrange
    $email = 'superadmin@example.com';
    $password = 'new-secret';
    $user = User::factory()->create(['email' => $email]);
    $user->assignRole(Role::SUPER_ADMIN->value);

    // Mock User entity to return false for hasProtectedStatus() directly
    $userMock = Mockery::mock(User::class)->makePartial();
    $userMock->shouldReceive('where')->andReturnSelf();
    $userMock->shouldReceive('firstOrFail')->andReturn($user);
    $userMock->shouldReceive('asSuperAdminIntegrityRules->hasProtectedStatus')->andReturnFalse(); // Simulate integrity violation
    $userMock->shouldReceive('hasRole')->andReturnTrue();
    app()->instance(User::class, $userMock);

    $action = app(RecoverSuperAdminAction::class);

    // Act & Assert
    $this->expectException(RejectedException::class);
    $this->expectExceptionMessage('Super admin account integrity violation: expected PROTECTED status.');
    $action->execute($email, $password);
});

test('C9ZB6-FR-P4: clears locked_at and locked_reason for the recovered user', function () {
    // Arrange
    $email = 'locked@example.com';
    $password = 'new-secret';
    $user = User::factory()->locked('manual-lock')->create(['email' => $email]);
    $user->assignRole(Role::SUPER_ADMIN->value);

    // Mock User entity to allow successful recovery
    $userMock = Mockery::mock(User::class)->makePartial();
    $userMock->shouldReceive('where')->andReturnSelf();
    $userMock->shouldReceive('firstOrFail')->andReturn($user);
    $userMock->shouldReceive('asSuperAdminIntegrityRules->hasProtectedStatus')->andReturnTrue();
    $userMock->shouldReceive('hasRole')->andReturnTrue();
    $userMock->shouldReceive('update')->andReturnTrue();
    $userMock->shouldReceive('syncRoles')->andReturnTrue();
    $userMock->shouldReceive('forceFill->save')->andReturnTrue();
    app()->instance(User::class, $userMock);

    $action = app(RecoverSuperAdminAction::class);

    // Act
    $recoveredUser = $action->execute($email, $password);

    // Assert
    expect($recoveredUser->locked_at)->toBeNull();
    expect($recoveredUser->locked_reason)->toBeNull();
});

test('C9ZB6-FR-P5: throws ModelNotFoundException if user with given email does not exist', function () {
    // Arrange
    $email = 'nonexistent@example.com';
    $password = 'anypassword';
    $action = app(RecoverSuperAdminAction::class);

    // Act & Assert
    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    $action->execute($email, $password);
});
