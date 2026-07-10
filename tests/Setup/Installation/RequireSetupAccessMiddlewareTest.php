<?php

declare(strict_types=1);

use Tests\Support\WithSettingsSeed;
use App\Setup\Installation\Http\Middleware\RequireSetupAccessMiddleware;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);
uses(WithSettingsSeed::class);

beforeEach(function () {
    $this->seedSettings([
        'setup.is_installed' => ['value' => false, 'group' => 'setup', 'type' => 'boolean'],
    ]);
    Cache::flush();

    Route::get('/_test_require_setup', function () {
        return 'ok';
    })->middleware(RequireSetupAccessMiddleware::class);

    Route::get('/_test_setup_page', function () {
        return 'setup page';
    })->name('setup');
});

test('allows access when system is installed', function () {
    $this->seedSettings([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
    ]);
    Cache::flush();

    $response = $this->get('/_test_require_setup');

    $response->assertStatus(200);
});

test('redirects to setup when system is not installed', function () {
    Cache::flush();

    $response = $this->get('/_test_require_setup');

    $response->assertRedirect(route('setup'));
});

test('allows access to setup route when not installed', function () {
    Cache::flush();

    $response = $this->get('/_test_setup_page');

    $response->assertStatus(200);
    $response->assertSee('setup page');
});

test('allows livewire requests when not installed', function () {
    Cache::flush();

    $response = $this->get('/_test_require_setup', [
        'X-Livewire' => 'true',
    ]);

    $response->assertStatus(200);
});
