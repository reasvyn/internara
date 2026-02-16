<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Services\Contracts\SettingService;


test('it resets setup state correctly', function () {
    $settingService = app(SettingService::class);

    // Initial state: Installed
    $settingService->setValue('app_installed', true);
    $settingService->setValue('setup_token', 'old-token');
    Cache::put('user.super_admin', true);

    $this->artisan('app:setup-reset')
        ->expectsConfirmation(
            'This will unlock the setup routes and allow reconfiguration. Continue?',
            'yes',
        )
        ->expectsOutputToContain('Setup state has been reset successfully!')
        ->assertSuccessful();

    expect($settingService->getValue('app_installed'))
        ->toBeFalse()
        ->and($settingService->getValue('setup_token'))
        ->not->toBe('old-token')
        ->and($settingService->getValue('setup_token'))
        ->not->toBeEmpty()
        ->and(Cache::has('user.super_admin'))
        ->toBeFalse();
});

test('it fails if reset not confirmed', function () {
    $this->artisan('app:setup-reset')
        ->expectsConfirmation(
            'This will unlock the setup routes and allow reconfiguration. Continue?',
            'no',
        )
        ->assertFailed();
});

test('it forces reset if flag provided', function () {
    $settingService = app(SettingService::class);
    $settingService->setValue('app_installed', true);

    $this->artisan('app:setup-reset', ['--force' => true])->assertSuccessful();

    expect($settingService->getValue('app_installed'))->toBeFalse();
});
