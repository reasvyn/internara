<?php

declare(strict_types=1);

namespace Modules\Department\Tests\Unit\Services;

use Modules\Department\Models\Department;
use Modules\Department\Services\DepartmentService;

test('it can search departments by name', function () {
    $department = mock(Department::class);
    $service = new DepartmentService($department);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $department->shouldReceive('newQuery')->andReturn($builder);
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

    $service->query(['search' => 'Informatics']);
    expect(true)->toBeTrue();
});
