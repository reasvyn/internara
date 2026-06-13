<?php

declare(strict_types=1);

use App\User\Models\User;
use App\User\UserManagement\Actions\DeleteUserAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'student']);
    $this->action = app(DeleteUserAction::class);
});

test('deletes a non-admin user', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $this->action->execute($user);

    $this->assertModelMissing($user);
});

test('rejects deleting super admin accounts', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect(fn () => $this->action->execute($user))
        ->toThrow(RuntimeException::class, 'Super administrator accounts cannot be deleted.');
});

test('rejects deleting your own account', function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    $this->actingAs($user);

    expect(fn () => $this->action->execute($user))
        ->toThrow(RuntimeException::class, 'You cannot delete your own account.');
});
