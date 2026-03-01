<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Models\School;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\SetupService;
use Modules\User\Services\Contracts\SuperAdminService;

describe('SetupService S1 Security', function () {
    beforeEach(function () {
        $this->settingService = $this->mock(SettingService::class);
        $this->superAdminService = $this->mock(SuperAdminService::class);
        $this->schoolService = $this->mock(SchoolService::class);
        $this->departmentService = $this->mock(DepartmentService::class);
        $this->internshipService = $this->mock(InternshipService::class);

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
        $this->settingService->shouldReceive('setValue')->once();

        $this->service->performSetupStep('welcome');
    });

    test('saveSystemSettings enforces authorization', function () {
        Gate::shouldReceive('authorize')->once()->with('saveSettings', SetupService::class);

        $this->settingService->shouldReceive('setValue')->once();

        $this->service->saveSystemSettings([]);
    });

    test('finalizeSetupStep enforces authorization', function () {
        Gate::shouldReceive('authorize')->once()->with('finalize', SetupService::class);

        $school = \Mockery::mock(School::class);
        $school->shouldReceive('getAttribute')->with('name')->andReturn('Test');
        $school->shouldReceive('getAttribute')->with('logo_url')->andReturn(null);

        $this->schoolService
            ->shouldReceive('getSchool')
            ->andReturn($school);
        
        $this->settingService->shouldReceive('getValue')
            ->with(SetupService::SETTING_APP_NAME, 'Internara')
            ->andReturn('Internara');

        $this->settingService->shouldReceive('setValue')->once();

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());
        DB::shouldReceive('connection')->andReturn(\Mockery::mock(\Illuminate\Database\ConnectionInterface::class));

        Session::shouldReceive('forget')->atLeast()->once();
        Session::shouldReceive('regenerate')->once();

        $this->service->finalizeSetupStep();
    });
});
