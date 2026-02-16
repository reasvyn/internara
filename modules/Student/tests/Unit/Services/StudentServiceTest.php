<?php

declare(strict_types=1);

namespace Modules\Student\Tests\Unit\Services;

use Modules\Student\Models\Student;
use Modules\Student\Services\StudentService;

test('it can create student with default values', function () {
    $student = mock(Student::class);
    $student
        ->shouldReceive('getFillable')
        ->andReturn(['registration_number', 'national_identifier', 'class_name']);

    $service = new StudentService($student);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $student->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('create')->once()->andReturn(new Student);

    $result = $service->createWithDefault();
    expect($result)->toBeInstanceOf(Student::class);
});
