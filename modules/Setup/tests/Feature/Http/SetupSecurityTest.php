<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\User\Services\Contracts\SuperAdminService;

uses(RefreshDatabase::class);

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

    $url = \Illuminate\Support\Facades\URL::signedRoute('setup.welcome', ['token' => $token]);

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
