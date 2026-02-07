<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\AttendanceService;

test('it can query attendance logs', function () {
    $log = mock(AttendanceLog::class);
    $service = new AttendanceService($log);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $log->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
