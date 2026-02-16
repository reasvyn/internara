<?php

declare(strict_types=1);

namespace Modules\Teacher\Tests\Unit\Services;

use Modules\Teacher\Models\Teacher;
use Modules\Teacher\Services\TeacherService;

test('it can create teacher with default values', function () {
    $teacher = mock(Teacher::class);
    $teacher->shouldReceive('getFillable')->andReturn(['nip']);

    $service = new TeacherService($teacher);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $teacher->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('create')->once()->andReturn(new Teacher);

    $result = $service->createWithDefault();
    expect($result)->toBeInstanceOf(Teacher::class);
});
