<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\AttendanceService;
use Modules\Internship\Services\Contracts\RegistrationService;

test('it can query attendance logs', function () {
    $log = mock(AttendanceLog::class);
    $registrationService = mock(RegistrationService::class);
    $service = new AttendanceService($registrationService, $log);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $log->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
