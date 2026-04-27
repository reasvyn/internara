<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\Internship\Models\Internship;
use Modules\Internship\Services\InternshipService;

test('it can query internships', function () {
    $internship = mock(Internship::class);
    $service = new InternshipService($internship, mock(AssignmentService::class));

    $builder = mock(Builder::class);
    $internship->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();
    $builder->shouldReceive('with')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(Builder::class);
});
