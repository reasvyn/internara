<?php

declare(strict_types=1);

namespace Modules\Schedule\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\ScheduleService;

test('it can query schedules', function () {
    $schedule = mock(Schedule::class);
    $service = new ScheduleService($schedule);

    $builder = mock(Builder::class);
    $schedule->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();
    $builder->shouldReceive('with')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(Builder::class);
});
