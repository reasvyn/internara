<?php

declare(strict_types=1);

namespace Modules\Assignment\Tests\Unit\Services;

use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Services\AssignmentService;
use Modules\Assignment\Services\Contracts\AssignmentTypeService;

test('it can query assignments', function () {
    $assignment = mock(Assignment::class);
    $service = new AssignmentService($assignment, mock(AssignmentTypeService::class));

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $assignment->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();
    $builder->shouldReceive('with')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
