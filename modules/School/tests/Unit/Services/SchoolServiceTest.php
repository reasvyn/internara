<?php

declare(strict_types=1);

namespace Modules\School\Tests\Unit\Services;

use Modules\School\Models\School;
use Modules\School\Services\SchoolService;

test('it can retrieve school instance', function () {
    $school = mock(School::class);
    $service = new SchoolService($school);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $school->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('first')->andReturn(new School);

    $result = $service->get();
    expect($result)->toBeInstanceOf(School::class);
});
