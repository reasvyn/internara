<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\SetupService;
use Modules\User\Services\Contracts\SuperAdminService;

describe('SetupService S1 Security', function () {
    beforeEach(function () {
        $this->settingService = Mockery::mock(SettingService::class);
        $this->superAdminService = Mockery::mock(SuperAdminService::class);
        $this->schoolService = Mockery::mock(SchoolService::class);
        $this->departmentService = Mockery::mock(DepartmentService::class);
        $this->internshipService = Mockery::mock(InternshipService::class);

        $this->service = new SetupService(
            $this->settingService,
            $this->superAdminService,
            $this->schoolService,
            $this->departmentService,
            $this->internshipService,
        );
    });

    test('performSetupStep enforces authorization', function () {
        Gate::shouldReceive('authorize')->once()->with('performStep', SetupService::class);

        $this->service->performSetupStep('welcome');
    });

    test('saveSystemSettings enforces authorization', function () {
        Gate::shouldReceive('authorize')->once()->with('saveSettings', SetupService::class);

        $this->settingService->shouldReceive('setValue')->once();

        $this->service->saveSystemSettings([]);
    });

    test('finalizeSetupStep enforces authorization', function () {
        Gate::shouldReceive('authorize')->once()->with('finalize', SetupService::class);

        $this->schoolService
            ->shouldReceive('getSchool')
            ->andReturn((object) ['name' => 'Test', 'logo_url' => null]);
        $this->settingService->shouldReceive('setValue')->once();

        $this->service->finalizeSetupStep();
    });
});
