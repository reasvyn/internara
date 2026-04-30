<?php

declare(strict_types=1);

use Modules\Permission\Models\Permission;
use Modules\Permission\Models\Role;
use Modules\Permission\Services\AccessManagementService;

it('can create a permission', function () {
    $service = app(AccessManagementService::class);
    
    $permission = $service->createPermission(
        name: 'test-permission',
        description: 'Test permission',
        module: 'test',
    );
    
    expect($permission)->toBeInstanceOf(Permission::class);
    expect($permission->name)->toBe('test-permission');
    expect($permission->module)->toBe('test');
});

it('can create a role', function () {
    $service = app(AccessManagementService::class);
    
    $role = $service->createRole(
        name: 'test-role',
        description: 'Test role',
        module: 'test',
    );
    
    expect($role)->toBeInstanceOf(Role::class);
    expect($role->name)->toBe('test-role');
});

it('can assign permissions to role', function () {
    $service = app(AccessManagementService::class);
    
    // Create permissions first
    $perm1 = $service->createPermission('perm-1', 'Permission 1', 'test');
    $perm2 = $service->createPermission('perm-2', 'Permission 2', 'test');
    
    // Create role
    $role = $service->createRole('test-role', 'Test', 'test');
    
    // Assign permissions
    $role = $service->assignPermissionsToRole(
        roleName: 'test-role',
        permissions: ['perm-1', 'perm-2'],
    );
    
    expect($role->permissions)->toHaveCount(2);
});

it('can delete a role', function () {
    $service = app(AccessManagementService::class);
    
    // Create role first
    $service->createRole('to-delete', 'To Delete', 'test');
    
    // Delete it
    $result = $service->deleteRole('to-delete');
    
    expect($result)->toBeTrue();
    expect(Role::where('name', 'to-delete')->exists())->toBeFalse();
});

it('can delete a permission', function () {
    $service = app(AccessManagementService::class);
    
    // Create permission first
    $service->createPermission('to-delete', 'To Delete', 'test');
    
    // Delete it
    $result = $service->deletePermission('to-delete');
    
    expect($result)->toBeTrue();
    expect(Permission::where('name', 'to-delete')->exists())->toBeFalse();
});

it('throws exception for non-existent role', function () {
    $service = app(AccessManagementService::class);
    
    expect(fn() => $service->deleteRole('non-existent'))
        ->toThrow(Spatie\Permission\Exceptions\RoleDoesNotExist::class);
});

it('throws exception for non-existent permission', function () {
    $service = app(AccessManagementService::class);
    
    expect(fn() => $service->deletePermission('non-existent'))
        ->toThrow(Spatie\Permission\Exceptions\PermissionDoesNotExist::class);
});
