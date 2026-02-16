<?php

declare(strict_types=1);

namespace Modules\Profile\Tests\Unit\Services;

use Modules\Permission\Enums\Role;
use Modules\Profile\Models\Profile;
use Modules\Profile\Services\ProfileService;
use Modules\Student\Services\Contracts\StudentService;
use Modules\Teacher\Services\Contracts\TeacherService;

test('it can get profile by user id', function () {
    $profile = mock(Profile::class);
    $service = new ProfileService(
        $profile,
        mock(StudentService::class),
        mock(TeacherService::class),
    );

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $profile->shouldReceive('newQuery')->andReturn($builder);
    $builder
        ->shouldReceive('firstOrCreate')
        ->with(['user_id' => 'user-uuid'])
        ->andReturn(new Profile());

    $result = $service->getByUserId('user-uuid');
    expect($result)->toBeInstanceOf(Profile::class);
});

test('it can sync student profileable', function () {
    $profileModel = mock(Profile::class);
    $studentService = mock(StudentService::class);
    $teacherService = mock(TeacherService::class);

    $service = new ProfileService($profileModel, $studentService, $teacherService);

    $profile = mock(Profile::class)->makePartial();
    $profile->profileable_id = null; // Important for trigger

    $student = mock(\Modules\Student\Models\Student::class);
    $studentService->shouldReceive('createWithDefault')->once()->andReturn($student);

    $relation = mock(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
    $profile->shouldReceive('profileable')->andReturn($relation);
    $relation->shouldReceive('associate')->with($student)->once();
    $profile->shouldReceive('save')->once();

    $result = $service->syncProfileable($profile, [Role::STUDENT->value]);
    expect($result)->toBe($profile);
});
