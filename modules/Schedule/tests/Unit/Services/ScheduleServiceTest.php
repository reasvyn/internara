<?php

declare(strict_types=1);

namespace Modules\Schedule\Tests\Unit\Services;

use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\ScheduleService;

test('it can query schedules', function () {
    $schedule = mock(Schedule::class);
    $service = new ScheduleService($schedule);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $schedule->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
