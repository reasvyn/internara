<?php

declare(strict_types=1);

namespace Modules\Profile\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Gate;
use Modules\Profile\Models\Profile;
use Modules\Profile\Services\ProfileService;
use Modules\User\Models\User;

describe('ProfileService S1 Security', function () {
    test('getByUserId enforces authorization', function () {
        $profileModel = mock(Profile::class);
        $service = new ProfileService($profileModel);

        $uuid = 'user-uuid';

        Gate::shouldReceive('authorize')
            ->once()
            ->with('view', [$profileModel, $uuid]);

        $builder = mock(Builder::class);
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
        $student = new class extends Model
        {
            protected $keyType = 'string';

            public function getKey()
            {
                return 'student-uuid';
            }
        };

        Gate::shouldReceive('authorize')->once()->with('update', $profile);

        $relation = mock(MorphTo::class);
        $profile->shouldReceive('profileable')->andReturn($relation);
        $relation->shouldReceive('associate')->with($student)->once();
        $profile->shouldReceive('save')->once();

        $service->syncProfileable($profile, $student);
    });

    test('upsertManagedProfile authorizes against managed user and persists data', function () {
        $profileModel = mock(Profile::class);
        $service = new ProfileService($profileModel);

        $uuid = 'user-uuid';
        $user = mock(User::class);
        $userBuilder = mock(Builder::class);

        mock('alias:Modules\User\Models\User')
            ->shouldReceive('query')
            ->once()
            ->andReturn($userBuilder);

        $userBuilder->shouldReceive('find')->once()->with($uuid)->andReturn($user);
        Gate::shouldReceive('authorize')->once()->with('update', $user);

        $builder = mock(Builder::class);
        $profile = mock(Profile::class)->makePartial();

        $profileModel->shouldReceive('newQuery')->once()->andReturn($builder);
        $builder
            ->shouldReceive('firstOrCreate')
            ->once()
            ->with(['user_id' => $uuid])
            ->andReturn($profile);
        $profile
            ->shouldReceive('fill')
            ->once()
            ->with(['phone' => '08123'])
            ->andReturnSelf();
        $profile->shouldReceive('save')->once();

        $service->upsertManagedProfile($uuid, ['phone' => '08123']);
    });
});
