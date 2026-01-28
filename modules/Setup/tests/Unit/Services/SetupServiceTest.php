<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Exception\AppException;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\School\Models\School;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\SetupService;
use Modules\User\Services\Contracts\SuperAdminService;
use Mockery;

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
        $this->internshipService
    );
});

test('it checks if app is installed', function () {
    $this->settingService->shouldReceive('getValue')->with('app_installed', false, true)->twice()->andReturn(true, false);
    
    expect($this->service->isAppInstalled())->toBeTrue();
    expect($this->service->isAppInstalled())->toBeFalse();
});

test('it checks if step is completed', function () {
    $this->settingService->shouldReceive('getValue')->with('setup_step_welcome', false)->andReturn(true);
    $this->settingService->shouldReceive('getValue')->with('setup_step_non_existent_step', false)->andReturn(false);

    expect($this->service->isStepCompleted('welcome'))
        ->toBeTrue()
        ->and($this->service->isStepCompleted('non_existent_step'))
        ->toBeFalse();
});

test('it checks if record exists', function () {
    $this->schoolService->shouldReceive('exists')->twice()->andReturn(true, false);
    
    expect($this->service->isRecordExists('school'))->toBeTrue();
    expect($this->service->isRecordExists('school'))->toBeFalse();
});

test('it throws exception if unknown record type requested', function () {
    $this->service->isRecordExists('unknown');
})->throws(\InvalidArgumentException::class);

test('it requires setup access', function () {
    // No prev step, check app_installed
    $this->settingService->shouldReceive('getValue')->with('app_installed', false, true)->andReturn(false);
    expect($this->service->requireSetupAccess())->toBeFalse();

    // With prev step, check step completion
    $this->settingService->shouldReceive('getValue')->with('setup_step_welcome', false)->andReturn(true);
    expect($this->service->requireSetupAccess('welcome'))->toBeTrue();

    // This should throw AppException because step is NOT completed
    $this->settingService->shouldReceive('getValue')->with('setup_step_fail', false)->andReturn(false);
    $this->service->requireSetupAccess('fail');
})->throws(AppException::class);

test('it performs setup step', function () {
    $this->settingService->shouldReceive('setValue')->with('setup_step_welcome', true)->once();
    expect($this->service->performSetupStep('welcome'))->toBeTrue();

    $this->schoolService->shouldReceive('exists')->andReturn(true);
    $this->settingService->shouldReceive('setValue')->with('setup_step_school_step', true)->once();
    expect($this->service->performSetupStep('school_step', 'school'))->toBeTrue();
});

test('it fails setup step if record missing', function () {
    $this->superAdminService->shouldReceive('exists')->andReturn(false);
    $this->service->performSetupStep('account', 'super-admin');
})->throws(AppException::class);

test('it finalizes setup step', function () {
    $school = Mockery::mock(School::class)->makePartial();
    $school->name = 'Test School';
    $school->logo_url = 'http://logo.com';

    $this->schoolService->shouldReceive('getSchool')->andReturn($school);

    $this->settingService->shouldReceive('setValue')->with(Mockery::on(function ($settings) {
        return $settings['brand_name'] === 'Test School' &&
               $settings['app_installed'] === true &&
               $settings['setup_token'] === null;
    }))->once();

    $this->settingService->shouldReceive('getValue')->with('app_installed', false, true)->andReturn(true);

    $result = $this->service->finalizeSetupStep();

    expect($result)->toBeTrue();
});
