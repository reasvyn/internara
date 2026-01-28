<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\User\Services\Contracts\SuperAdminService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

test('it redirects to welcome if app is not installed', function () {
    $settingMock = Mockery::mock(SettingService::class);
    $settingMock->shouldReceive('getValue')->with('app_installed', false, Mockery::any())->andReturn(false);
    $this->app->instance(SettingService::class, $settingMock);

    // Any non-setup route should redirect to welcome
    // Using '/' which is a simple redirect route
    $this->withHeaders(['X-Test-No-Console' => 'true'])
        ->get('/')
        ->assertRedirect(route('setup.welcome'));
});

test('it aborts 403 on setup routes if token is missing or invalid', function () {
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'valid-token-123');

    // No token
    $this->get(route('setup.welcome'))->assertStatus(403);

    // Invalid token
    $this->get(route('setup.welcome', ['token' => 'wrong-token']))->assertStatus(403);
});

test('it allows setup access if valid token is provided', function () {
    app(SettingService::class)->setValue('app_installed', false);
    $token = Str::random(32);
    app(SettingService::class)->setValue('setup_token', $token);

    $this->get(route('setup.welcome', ['token' => $token]))
        ->assertOk()
        ->assertSessionHas('setup_authorized', true);
});

test('it hides setup routes with 404 once installed and superadmin exists', function () {
    app(SettingService::class)->setValue('app_installed', true);

    // Setup a superadmin to trigger lockdown
    $superAdminService = app(SuperAdminService::class);
    $superAdminService->factory()->create()->assignRole('super-admin');

    $this->get(route('setup.welcome'))->assertStatus(404);
});

test('it prevents setup access if setup_token is purged from database', function () {
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', null); // Purged

    // Even if session exists (e.g. from previous steps)
    $this->withSession(['setup_authorized' => true])
        ->get(route('setup.welcome'))
        ->assertStatus(403);
});