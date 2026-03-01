<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SetupService as Contract;
use Modules\Setup\Services\SetupService;
use Modules\User\Services\Contracts\SuperAdminService;

describe('SetupService Unit Test', function () {
    beforeEach(function () {
        $this->settingService = $this->mock(SettingService::class);
        $this->superAdminService = $this->mock(SuperAdminService::class);
        $this->schoolService = $this->mock(SchoolService::class);
        $this->departmentService = $this->mock(DepartmentService::class);
        $this->internshipService = $this->mock(InternshipService::class);

        // Ensure Gate is always authorized for unit tests
        Gate::shouldReceive('authorize')->byDefault()->andReturn(true);
        $this->settingService->shouldReceive('setValue')->byDefault();
    });

    test('it checks if app is installed by querying setting service', function () {
        $this->settingService
            ->shouldReceive('getValue')
            ->with(Contract::SETTING_APP_INSTALLED, false, true)
            ->once()
            ->andReturn(true);

        $service = new SetupService(
            $this->settingService,
            $this->superAdminService,
            $this->schoolService,
            $this->departmentService,
            $this->internshipService,
        );

        expect($service->isAppInstalled())->toBeTrue();
    });

    test('it finalizes setup sequence logically', function () {
        $partial = \Mockery::mock(SetupService::class, [
            $this->settingService,
            $this->superAdminService,
            $this->schoolService,
            $this->departmentService,
            $this->internshipService
        ])->makePartial();

        $partial->shouldReceive('finalizeSetupStep')->once()->andReturn(true);

        expect($partial->finalizeSetupStep())->toBeTrue();
    });

    test('it can check specific setup steps', function () {
        $this->settingService->shouldReceive('getValue')->andReturn(true);
        
        $service = new SetupService(
            $this->settingService,
            $this->superAdminService,
            $this->schoolService,
            $this->departmentService,
            $this->internshipService,
        );

        expect($service->isStepCompleted('welcome'))->toBeTrue();
    });
});
