<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Modules\Setup\Events\SetupFinalized;
use Modules\Setup\Services\SetupService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Internship\Services\Contracts\InternshipService;

describe('SetupService Feature Test', function () {
    beforeEach(function () {
        $this->settingService = $this->mock(SettingService::class);
        $this->superAdminService = $this->mock(SuperAdminService::class);
        $this->schoolService = $this->mock(SchoolService::class);
        $this->departmentService = $this->mock(DepartmentService::class);
        $this->internshipService = $this->mock(InternshipService::class);

        $this->setupService = new SetupService(
            $this->settingService,
            $this->superAdminService,
            $this->schoolService,
            $this->departmentService,
            $this->internshipService
        );
    });

    test('it can finalize setup atomically and dispatch event', function () {
        Event::fake();
        Session::start();

        $schoolMock = (object) [
            'name' => 'SMK Internara Test',
            'logo_url' => 'https://example.com/logo.png'
        ];

        $this->schoolService->shouldReceive('getSchool')->once()->andReturn($schoolMock);
        
        // Mock app_name retrieval
        $this->settingService->shouldReceive('getValue')
            ->with(SetupService::SETTING_APP_NAME, 'Internara')
            ->once()
            ->andReturn('Internara');

        // Verify branding and app_installed settings
        $this->settingService->shouldReceive('setValue')
            ->with(\Mockery::on(function ($settings) use ($schoolMock) {
                return $settings[SetupService::SETTING_BRAND_NAME] === $schoolMock->name &&
                       $settings[SetupService::SETTING_APP_INSTALLED] === true &&
                       $settings[SetupService::SETTING_SETUP_TOKEN] === null &&
                       str_contains($settings[SetupService::SETTING_SITE_TITLE], 'SMK Internara Test');
            }))
            ->once();

        // Verify session cleanup
        session(['setup_authorized' => true, 'setup_step_1' => true]);

        $this->settingService->shouldReceive('getValue')
            ->with(SetupService::SETTING_APP_INSTALLED, false, true)
            ->once()
            ->andReturn(true);

        $result = $this->setupService->finalizeSetupStep();

        expect($result)->toBeTrue();
        
        // Verify Event
        Event::assertDispatched(SetupFinalized::class, function ($event) use ($schoolMock) {
            return $event->schoolName === $schoolMock->name;
        });

        // Verify session was cleared
        expect(session('setup_authorized'))->toBeNull();
    });
});
