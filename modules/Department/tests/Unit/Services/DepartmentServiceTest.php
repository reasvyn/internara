<?php

declare(strict_types=1);

namespace Modules\Department\Tests\Unit\Services;

use Modules\Department\Models\Department;
use Modules\Department\Services\DepartmentService;
use Modules\School\Services\Contracts\SchoolService;

test('it can search departments by name', function () {
    $department = mock(Department::class);
    $schoolService = mock(SchoolService::class);
    $service = new DepartmentService($department, $schoolService);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $department->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();
    $builder->shouldReceive('with')->andReturnSelf();

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
