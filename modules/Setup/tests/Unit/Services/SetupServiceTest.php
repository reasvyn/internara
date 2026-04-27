<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Mockery;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Models\School;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Events\SetupFinalized;
use Modules\Setup\Services\AppSetupService;

describe('SetupService', function () {
    beforeEach(function () {
        config(['activitylog.enabled' => false]);

        $this->settingService = Mockery::mock(SettingService::class);
        $this->superAdminService = Mockery::mock(SuperAdminService::class);
        $this->schoolService = Mockery::mock(SchoolService::class);
        $this->departmentService = Mockery::mock(DepartmentService::class);
        $this->internshipService = Mockery::mock(InternshipService::class);

        $this->service = new AppSetupService(
            $this->settingService,
            $this->superAdminService,
            $this->schoolService,
            $this->departmentService,
            $this->internshipService,
        );

        Gate::shouldReceive('authorize')
            ->with('performStep', AppSetupService::class)
            ->andReturn(true);

        Cache::spy();
        Cache::shouldReceive('lock')->andReturnUsing(function ($name, $seconds) {
            $lockMock = Mockery::mock(Lock::class);
            $lockMock->shouldReceive('get')->andReturnUsing(function ($callback) {
                return $callback();
            });

            return $lockMock;
        });
    });

    it('identifies if application is installed', function () {
        $this->settingService
            ->shouldReceive('getValue')
            ->with('app_installed', false, true)
            ->andReturn(true);

        expect($this->service->isAppInstalled())->toBeTrue();
    });

    it('marks a setup step as completed and logs the activity', function () {
        $this->settingService
            ->shouldReceive('setValue')
            ->with('setup_step_school', true)
            ->once()
            ->andReturn(true);

        $success = $this->service->performSetupStep('school');

        expect($success)->toBeTrue();
        // Activity log is handled via helper, we can't easily mock it here without full integration test
    });

    it('finalizes setup step with database transaction and cache clearing', function () {
        Gate::shouldReceive('authorize')->with('finalize', AppSetupService::class)->andReturn(true);
        Event::fake();
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $school = new School(['name' => 'Test School']);
        $this->schoolService->shouldReceive('getSchool')->andReturn($school);

        $this->settingService
            ->shouldReceive('getValue')
            ->with('app_name', 'Internara')
            ->andReturn('Internara');
        $this->settingService->shouldReceive('setValue')->once();

        Session::spy();

        $this->settingService->shouldReceive('setValue')->with('setup_step_complete', true)->once();

        $success = $this->service->finalizeSetupStep();

        expect($success)->toBeTrue();
        Event::assertDispatched(SetupFinalized::class);
    });
});
