<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\AppException;
use App\Domain\Core\Exceptions\DomainException;
use App\Domain\User\Models\User;
use Livewire\Attributes\Layout;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// ---------------------------------------------------------------------------
// Authentication & Authorization Helpers
// ---------------------------------------------------------------------------

/**
 * Create a user with the super_admin role and act as them.
 */
function actingAsSuperAdmin(): TestCase
{
    $role = Role::firstOrCreate(
        ['name' => 'super_admin', 'guard_name' => 'web'],
    );

    $user = User::factory()->create();
    $user->assignRole($role);

    return test()->actingAs($user);
}

/**
 * Create a user with the admin role and act as them.
 */
function actingAsAdmin(): TestCase
{
    $role = Role::firstOrCreate(
        ['name' => 'admin', 'guard_name' => 'web'],
    );

    $user = User::factory()->create();
    $user->assignRole($role);

    return test()->actingAs($user);
}

/**
 * Create a user with the teacher role and act as them.
 */
function actingAsTeacher(): TestCase
{
    $role = Role::firstOrCreate(
        ['name' => 'teacher', 'guard_name' => 'web'],
    );

    $user = User::factory()->create();
    $user->assignRole($role);

    return test()->actingAs($user);
}

/**
 * Create a user with the student role and act as them.
 */
function actingAsStudent(): TestCase
{
    $role = Role::firstOrCreate(
        ['name' => 'student', 'guard_name' => 'web'],
    );

    $user = User::factory()->create();
    $user->assignRole($role);

    return test()->actingAs($user);
}

/**
 * Create a user with the supervisor role and act as them.
 */
function actingAsSupervisor(): TestCase
{
    $role = Role::firstOrCreate(
        ['name' => 'supervisor', 'guard_name' => 'web'],
    );

    $user = User::factory()->create();
    $user->assignRole($role);

    return test()->actingAs($user);
}

// ---------------------------------------------------------------------------
// Exception Assertion Helpers
// ---------------------------------------------------------------------------

/**
 * Assert that a thrown exception extends AppException.
 */
expect()->extend('toBeAppException', function () {
    return $this->toBeInstanceOf(AppException::class);
});

/**
 * Assert that a thrown exception extends DomainException.
 */
expect()->extend('toBeDomainException', function () {
    return $this->toBeInstanceOf(DomainException::class);
});

// ---------------------------------------------------------------------------
// Architecture Test Helpers
// ---------------------------------------------------------------------------

/**
 * Assert that all action classes in the given namespaces have an execute method.
 */
function assertActionsHaveExecute(): void
{
    $namespaces = func_get_args() ?: ['App\Domain\*\Actions'];

    foreach ($namespaces as $namespace) {
        arch("actions in {$namespace} have execute method")
            ->expect($namespace)
            ->toHaveMethod('execute');
    }
}

/**
 * Assert that all entity classes in the given namespaces are final readonly.
 */
function assertEntitiesAreFinalReadonly(): void
{
    $namespaces = func_get_args() ?: ['App\Domain\*\Entities'];

    foreach ($namespaces as $namespace) {
        arch("entities in {$namespace} are final readonly")
            ->expect($namespace)
            ->toBeClasses()
            ->toHaveAttribute(Layout::class);
    }
}
