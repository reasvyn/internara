<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Exception\AppException;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Models\School;
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

        $this->service = new SetupService(
            $this->settingService,
            $this->superAdminService,
            $this->schoolService,
            $this->departmentService,
            $this->internshipService,
        );

        // Global Gate mock for Unit Tests
        Gate::shouldReceive('authorize')->andReturn(true);
    });

    test('it checks if app is installed', function () {
        $this->settingService
            ->shouldReceive('getValue')
            ->with(Contract::SETTING_APP_INSTALLED, false, true)
            ->twice()
            ->andReturn(true, false);

        expect($this->service->isAppInstalled())->toBeTrue();
        expect($this->service->isAppInstalled())->toBeFalse();
    });

    test('it finalizes setup step', function () {
        $school = \Mockery::mock(School::class);
        $school->shouldReceive('getAttribute')->with('name')->andReturn('Test School');
        $school->shouldReceive('getAttribute')->with('logo_url')->andReturn('http://logo.com');

        $this->schoolService->shouldReceive('getSchool')->andReturn($school);

        // Mock app_name retrieval
        $this->settingService->shouldReceive('getValue')
            ->with(Contract::SETTING_APP_NAME, 'Internara')
            ->andReturn('Internara');

        $this->settingService
            ->shouldReceive('setValue')
            ->with(
                \Mockery::on(function ($settings) {
                    return $settings[Contract::SETTING_BRAND_NAME] === 'Test School' &&
                        $settings[Contract::SETTING_APP_INSTALLED] === true &&
                        $settings[Contract::SETTING_SETUP_TOKEN] === null;
                }),
            )
            ->once();

        $this->settingService
            ->shouldReceive('getValue')
            ->with(Contract::SETTING_APP_INSTALLED, false, true)
            ->andReturn(true);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());
        DB::shouldReceive('connection')->andReturn(\Mockery::mock(\Illuminate\Database\ConnectionInterface::class));

        // Mock Session cleanup
        Session::shouldReceive('forget')->atLeast()->once();
        Session::shouldReceive('regenerate')->once();

        $result = $this->service->finalizeSetupStep();

        expect($result)->toBeTrue();
    });
});
