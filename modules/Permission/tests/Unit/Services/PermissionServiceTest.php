<?php

declare(strict_types=1);

namespace Modules\Permission\Tests\Unit\Services;

use Modules\Permission\Models\Permission;
use Modules\Permission\Services\PermissionService;

test('it can search permissions by name', function () {
    $permission = mock(Permission::class);
    $service = new PermissionService($permission);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $permission->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();

    // expectations for applyFilters
    $builder
        ->shouldReceive('where')
        ->once()
        ->andReturnUsing(function ($callback) use ($builder) {
            $callback($builder);

            return $builder;
        });
    $builder->shouldReceive('orWhere')->atLeast()->once();

    $service->query(['search' => 'test-permission']);
    expect(true)->toBeTrue();
});
