<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Exception\AppException;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\SetupService;
use Modules\User\Services\Contracts\SuperAdminService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    $this->settingService = app(SettingService::class);
    $this->superAdminService = app(SuperAdminService::class);
    $this->schoolService = app(SchoolService::class);
    $this->departmentService = app(DepartmentService::class);
    $this->internshipService = app(InternshipService::class);

    $this->service = new SetupService(
        $this->settingService,
        $this->superAdminService,
        $this->schoolService,
        $this->departmentService,
        $this->internshipService,
    );
});

test('it checks if app is installed', function () {
    $this->settingService->setValue('app_installed', true);
    expect($this->service->isAppInstalled())->toBeTrue();

    $this->settingService->setValue('app_installed', false);
    expect($this->service->isAppInstalled())->toBeFalse();
});

test('it checks if step is completed', function () {
    Session::put('setup:welcome', true);
    expect($this->service->isStepCompleted('welcome'))
        ->toBeTrue()
        ->and($this->service->isStepCompleted('non_existent_step'))
        ->toBeFalse();
});

test('it checks if record exists', function () {
    expect($this->service->isRecordExists('school'))->toBeFalse();

    $this->schoolService->factory()->create();
    expect($this->service->isRecordExists('school'))->toBeTrue();
});

test('it throws exception if unknown record type requested', function () {
    $this->service->isRecordExists('unknown');
})->throws(\InvalidArgumentException::class);

test('it requires setup access', function () {
    $this->settingService->setValue('app_installed', false);
    expect($this->service->requireSetupAccess())->toBeFalse();

    Session::put('setup:welcome', true);
    expect($this->service->requireSetupAccess('welcome'))->toBeTrue();

    Session::forget('setup:welcome');
    $this->service->requireSetupAccess('welcome');
})->throws(AppException::class);

test('it performs setup step', function () {
    expect($this->service->performSetupStep('welcome'))->toBeTrue();
    expect(Session::get('setup:welcome'))->toBeTrue();

    $this->schoolService->factory()->create();
    expect($this->service->performSetupStep('school_step', 'school'))->toBeTrue();
    expect(Session::get('setup:school_step'))->toBeTrue();
});

test('it fails setup step if record missing', function () {
    $this->service->performSetupStep('account', 'super-admin');
})->throws(AppException::class);

test('it finalizes setup step', function () {
    $school = $this->schoolService->factory()->create([
        'name' => 'Test School',
    ]);

    $result = $this->service->finalizeSetupStep();

    expect($result)
        ->toBeTrue()
        ->and($this->settingService->getValue('brand_name'))
        ->toBe('Test School')
        ->and($this->settingService->getValue('app_installed'))
        ->toBeTrue();
});
