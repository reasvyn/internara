<?php

declare(strict_types=1);

use App\Setup\Models\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::get('/_test_setup_protect', function () {
        return 'ok';
    })->middleware('setup.protected');
});

test('blocks access without token when system is installed', function () {
    Setup::create(['is_installed' => true]);
    Cache::flush();

    $response = $this->get('/_test_setup_protect');

    $response->assertStatus(404);
});

test('allows access when session has completed flag', function () {
    Setup::create(['is_installed' => true]);
    Cache::flush();
    Session::put('setup.completed', true);

    $response = $this->get('/_test_setup_protect');

    $response->assertStatus(200);
});

test('shows token entry form when system is not installed and no token', function () {
    Setup::truncate();
    Cache::flush();

    $response = $this->get('/_test_setup_protect');

    $response->assertStatus(200);
});
