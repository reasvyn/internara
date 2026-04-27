<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Http;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\AppSetupService;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

test('it aborts 403 on setup routes if token is missing or invalid', function () {
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'valid-token-123');

    $this->get(route('setup.welcome'))->assertStatus(403);
    $this->get(route('setup.welcome', ['token' => 'wrong-token']))->assertStatus(403);
});

test('it allows setup access if valid token and signature are provided', function () {
    app(SettingService::class)->setValue('app_installed', false);
    $token = Str::random(32);
    app(SettingService::class)->setValue('setup_token', $token);

    $url = URL::signedRoute('setup.welcome', ['token' => $token]);

    $this->get($url)->assertOk()->assertSessionHas('setup_authorized', true);
});

test('it hides setup routes with 404 once installed and superadmin exists', function () {
    app(SettingService::class)->setValue('app_installed', true);
    app(SuperAdminService::class)->factory()->create()->assignRole('super-admin');

    $this->get(route('setup.welcome'))->assertStatus(404);
});

test('it prevents setup access if setup_token is purged from database', function () {
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', null);

    $this->withSession(['setup_authorized' => true])
        ->get(route('setup.welcome'))
        ->assertStatus(403);
});

test('it redirects setup routes to setup complete when only finalization remains', function () {
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'valid-token-123');

    foreach (
        [
            AppSetupService::STEP_WELCOME,
            AppSetupService::STEP_ENVIRONMENT,
            AppSetupService::STEP_SCHOOL,
            AppSetupService::STEP_ACCOUNT,
            AppSetupService::STEP_DEPARTMENT,
            AppSetupService::STEP_INTERNSHIP,
            AppSetupService::STEP_SYSTEM,
        ]
        as $step
    ) {
        app(SettingService::class)->setValue("setup_step_{$step}", true);
    }

    app(SettingService::class)->setValue('setup_step_complete', false);

    $this->get(route('setup.welcome', ['token' => 'valid-token-123']))->assertRedirect(
        route('setup.complete'),
    );
});

test('it does not redirect setup complete to avoid redirect loops', function () {
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'valid-token-123');

    foreach (
        [
            AppSetupService::STEP_WELCOME,
            AppSetupService::STEP_ENVIRONMENT,
            AppSetupService::STEP_SCHOOL,
            AppSetupService::STEP_ACCOUNT,
            AppSetupService::STEP_DEPARTMENT,
            AppSetupService::STEP_INTERNSHIP,
            AppSetupService::STEP_SYSTEM,
        ]
        as $step
    ) {
        app(SettingService::class)->setValue("setup_step_{$step}", true);
    }

    app(SettingService::class)->setValue('setup_step_complete', false);

    $this->get(route('setup.complete', ['token' => 'valid-token-123']))->assertOk();
});
