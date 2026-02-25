<?php

declare(strict_types=1);

namespace Modules\Profile\Tests\Unit\Services;

use Modules\Profile\Models\Profile;
use Modules\Profile\Services\ProfileService;

test('it can get profile by user id', function () {
    $profile = mock(Profile::class);
    $service = new ProfileService($profile);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $profile->shouldReceive('newQuery')->andReturn($builder);
    $builder
        ->shouldReceive('firstOrCreate')
        ->with(['user_id' => 'user-uuid'])
        ->andReturn(new Profile);

    $result = $service->getByUserId('user-uuid');
    expect($result)->toBeInstanceOf(Profile::class);
});

test('it can sync profileable model', function () {
    $profileModel = mock(Profile::class);
    $service = new ProfileService($profileModel);

    $profile = mock(Profile::class)->makePartial();
    $profile->profileable_id = null;

    $student = mock(\Modules\Student\Models\Student::class);
    $student->shouldReceive('getKey')->andReturn('student-uuid');

    $relation = mock(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
    $profile->shouldReceive('profileable')->andReturn($relation);
    $relation->shouldReceive('associate')->with($student)->once();
    $profile->shouldReceive('save')->once();

    $result = $service->syncProfileable($profile, $student);
    expect($result)->toBe($profile);
});
