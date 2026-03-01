<?php

declare(strict_types=1);

namespace Modules\Profile\Tests\Unit\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Profile\Models\Profile;
use Modules\Profile\Services\ProfileService;

describe('ProfileService S1 Security', function () {
    test('getByUserId enforces authorization', function () {
        $profileModel = mock(Profile::class);
        $service = new ProfileService($profileModel);

        $uuid = 'user-uuid';

        Gate::shouldReceive('authorize')
            ->once()
            ->with('view', [Profile::class, $uuid]);

        $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
        $profileModel->shouldReceive('newQuery')->andReturn($builder);
        $builder
            ->shouldReceive('firstOrCreate')
            ->with(['user_id' => $uuid])
            ->andReturn(new Profile);

        $service->getByUserId($uuid);
    });

    test('syncProfileable enforces authorization', function () {
        $profileModel = mock(Profile::class);
        $service = new ProfileService($profileModel);

        $profile = mock(Profile::class)->makePartial();
        $student = new class extends \Illuminate\Database\Eloquent\Model
        {
            protected $keyType = 'string';

            public function getKey()
            {
                return 'student-uuid';
            }
        };

        Gate::shouldReceive('authorize')->once()->with('update', $profile);

        $relation = mock(\Illuminate\Database\Eloquent\Relations\MorphTo::class);
        $profile->shouldReceive('profileable')->andReturn($relation);
        $relation->shouldReceive('associate')->with($student)->once();
        $profile->shouldReceive('save')->once();

        $service->syncProfileable($profile, $student);
    });
});
