<?php

declare(strict_types=1);

use App\Domain\Admin\Actions\DeleteUserAction;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

describe('DeleteUserAction', function () {
    beforeEach(function () {
        RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    });

    it('prevents self-deletion', function () {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN->value);

        $this->actingAs($user);

        $action = app(DeleteUserAction::class);

        $action->execute($user);
    })->throws(RuntimeException::class, 'Super administrator accounts cannot be deleted.');
});
