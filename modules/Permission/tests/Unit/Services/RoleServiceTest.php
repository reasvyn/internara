<?php

declare(strict_types=1);

namespace Modules\Permission\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permission\Models\Role;
use Modules\Permission\Services\RoleService;

uses(RefreshDatabase::class);

test('it can list roles with filtering', function () {
    Role::create(['name' => 'admin', 'module' => 'core']);
    Role::create(['name' => 'student', 'module' => 'internship']);

    $service = new RoleService(new Role());

    $results = $service->paginate(['module' => 'core']);

    expect($results->total())
        ->toBe(1)
        ->and($results->first()->name)
        ->toBe('admin');
});

test('it can sync permissions', function () {
    $role = Role::create(['name' => 'test-role']);
    $permission = \Modules\Permission\Models\Permission::create(['name' => 'test-permission']);

    $service = new RoleService(new Role());
    $service->syncPermissions($role->id, ['test-permission']);

    expect($role->fresh()->hasPermissionTo('test-permission'))->toBeTrue();
});
