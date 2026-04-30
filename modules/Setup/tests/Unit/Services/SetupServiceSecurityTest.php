<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\AppSetupService;

describe('SetupService S1 Security', function () {
    beforeEach(function () {
        $this->settingService = $this->mock(SettingService::class);
        $this->superAdminService = $this->mock(SuperAdminService::class);
        $this->schoolService = $this->mock(SchoolService::class);
        $this->departmentService = $this->mock(DepartmentService::class);
        $this->internshipService = $this->mock(InternshipService::class);

        $this->settingService->shouldReceive('setValue')->byDefault();
    });

    test('performSetupStep enforces authorization', function () {
        Gate::shouldReceive('authorize')->once()->with('performStep', AppSetupService::class);

        // We call the actual method but ignore its internal logic impact
        $service = new AppSetupService(
            $this->settingService,
            $this->superAdminService,
            $this->schoolService,
            $this->departmentService,
            $this->internshipService,
        );

        try {
            $service->performSetupStep('welcome');
        } catch (\Throwable) {
            // We only care about the gate call
        }
    });

    test('finalizeSetupStep enforces authorization', function () {
        Gate::shouldReceive('authorize')->once()->with('finalize', AppSetupService::class);

        $service = new AppSetupService(
            $this->settingService,
            $this->superAdminService,
            $this->schoolService,
            $this->departmentService,
            $this->internshipService,
        );

        try {
            $service->finalizeSetupStep();
        } catch (\Throwable) {
            // We only care about the gate call
        }
    });
});
