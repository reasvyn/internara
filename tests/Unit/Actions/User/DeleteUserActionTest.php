<?php

declare(strict_types=1);

use App\Actions\User\DeleteUserAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('deletes a user', function () {
        $user = UserFactory::new()->create();

        app(DeleteUserAction::class)->execute($user);

        expect($user->fresh())->toBeNull();
    });

    it('throws RuntimeException when deleting self', function () {
        $user = UserFactory::new()->create();

        Auth::shouldReceive('id')->andReturn($user->id);

        expect(fn () => app(DeleteUserAction::class)->execute($user))
            ->toThrow(RuntimeException::class, 'You cannot delete your own account.');
    });

    it('throws RuntimeException when deleting last super admin', function () {
        $admin = UserFactory::new()->create();
        $admin->assignRole('super_admin');

        expect(fn () => app(DeleteUserAction::class)->execute($admin))
            ->toThrow(RuntimeException::class, 'Cannot delete the last administrator account.');
    });
});
