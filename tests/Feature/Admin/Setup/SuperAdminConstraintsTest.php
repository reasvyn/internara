<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Setup;

use App\Domain\Admin\Aggregates\Account\Actions\UpdateUserAction;
use App\Domain\Admin\Aggregates\Setup\Actions\SetupSuperAdminAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\User\Aggregates\Profile\Actions\UpdateProfileAction;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed Spatie roles
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('superadmin account cannot be deleted via Model or Event', function () {
    $superadmin = User::factory()->create([
        'email' => 'super@example.com',
        'username' => 'superadmin',
    ]);
    $superadmin->assignRole('superadmin');
    $superadmin->setStatus(AccountStatus::PROTECTED->value);

    // Assert that delete() method throws a RuntimeException
    expect(fn () => $superadmin->delete())->toThrow(
        RuntimeException::class,
        'Super administrator accounts cannot be deleted.'
    );

    // Verify user still exists in DB
    expect(User::where('email', 'super@example.com')->exists())->toBeTrue();
});

test('ensure there must and can only be one superadmin in the database', function () {
    // Create first superadmin
    $action = app(SetupSuperAdminAction::class);
    $action->execute('super1@example.com', 'SecurePassword123!');

    expect(User::role('superadmin')->count())->toBe(1);

    // Attempting to run SetupSuperAdminAction again throws RejectedException
    expect(fn () => $action->execute('super2@example.com', 'SecurePassword123!'))
        ->toThrow(RejectedException::class, 'Super admin already exists and cannot be re-initialized.');

    // Verify there is still exactly one superadmin
    expect(User::role('superadmin')->count())->toBe(1);

    // Attempting to run admin:create command also fails when one already exists
    $this->artisan('admin:create newadmin@example.com SecurePassword123!')
        ->expectsOutputToContain(__('admin.create.already_exists'))
        ->assertFailed();
});

test('superadmin name and username must always be Administrator and superadmin permanently', function () {
    $action = app(SetupSuperAdminAction::class);
    $superadmin = $action->execute('super@example.com', 'SecurePassword123!');

    // Default configuration values
    $expectedName = config('setup.defaults.admin_name', 'Administrator');
    $expectedUsername = config('setup.defaults.admin_username', 'superadmin');

    expect($superadmin->name)->toBe($expectedName);
    expect($superadmin->username)->toBe($expectedUsername);

    // Attempt to change name via UpdateUserAction throws RejectedException
    $updateUserAction = app(UpdateUserAction::class);
    expect(fn () => $updateUserAction->execute($superadmin, ['name' => 'New Name']))
        ->toThrow(RejectedException::class, 'Cannot change super admin name.');

    // Attempt to change username via UpdateUserAction throws RejectedException
    expect(fn () => $updateUserAction->execute($superadmin, ['username' => 'newusername']))
        ->toThrow(RejectedException::class, 'Cannot change super admin username.');

    // Attempt to change name via UpdateProfileAction throws RejectedException
    $updateProfileAction = app(UpdateProfileAction::class);
    expect(fn () => $updateProfileAction->execute($superadmin, [], 'New Name'))
        ->toThrow(RejectedException::class, 'Cannot change super admin name.');

    // Refresh model and verify attributes are unchanged
    $superadmin->refresh();
    expect($superadmin->name)->toBe($expectedName);
    expect($superadmin->username)->toBe($expectedUsername);
});
