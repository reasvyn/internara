<?php

declare(strict_types=1);

use App\Academics\Http\Middleware\RequireSetupAccessMiddleware;
use App\Setup\Models\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::get('/_test_require_setup', function () {
        return 'ok';
    })->middleware(RequireSetupAccessMiddleware::class);

    Route::get('/setup', function () {
        return 'setup page';
    })->name('setup');
});

test('allows access when system is installed', function () {
    Setup::create(['is_installed' => true]);
    Cache::flush();

    $response = $this->get('/_test_require_setup');

    $response->assertStatus(200);
});

test('redirects to setup when system is not installed', function () {
    Setup::truncate();
    Cache::flush();

    $response = $this->get('/_test_require_setup');

    $response->assertRedirect(route('setup'));
});

test('allows access to setup route when not installed', function () {
    Setup::truncate();
    Cache::flush();

    $response = $this->get('/setup');

    $response->assertStatus(200);
    $response->assertSee('setup page');
});

test('allows livewire requests when not installed', function () {
    Setup::truncate();
    Cache::flush();

    $response = $this->get('/_test_require_setup', [
        'X-Livewire' => 'true',
    ]);

    $response->assertStatus(200);
});
