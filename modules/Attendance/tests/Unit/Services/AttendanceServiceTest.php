<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\AttendanceService;
use Modules\Internship\Services\Contracts\RegistrationService;

describe('Attendance Service', function () {
    beforeEach(function () {
        $this->registrationService = mock(RegistrationService::class);
        $this->model = mock(AttendanceLog::class);
        $this->service = new AttendanceService($this->registrationService, $this->model);
    });

    test('it enforces authorization for attendance recording [SYRS-NF-502]', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('create', AttendanceLog::class)
            ->andThrow(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->service->recordAttendance('student-uuid', ['status' => 'present']);
    })->throws(\Illuminate\Auth\Access\AuthorizationException::class);
});
