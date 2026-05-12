<?php

declare(strict_types=1);

use App\Actions\User\UpdateRolePermissionsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('syncs permissions to a role', function () {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $perm1 = Permission::create(['name' => 'edit articles', 'guard_name' => 'web']);
        $perm2 = Permission::create(['name' => 'view articles', 'guard_name' => 'web']);

        app(UpdateRolePermissionsAction::class)->execute($role, [
            'edit articles',
            'view articles',
        ]);

        expect($role->fresh()->permissions)->toHaveCount(2);
    });

    it('removes permissions not in the list', function () {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $perm1 = Permission::create(['name' => 'edit articles', 'guard_name' => 'web']);
        $perm2 = Permission::create(['name' => 'delete articles', 'guard_name' => 'web']);
        $role->givePermissionTo($perm1, $perm2);

        app(UpdateRolePermissionsAction::class)->execute($role, ['edit articles']);

        expect($role->fresh()->permissions)->toHaveCount(1)
            ->and($role->fresh()->permissions->first()->name)->toBe('edit articles');
    });
});
