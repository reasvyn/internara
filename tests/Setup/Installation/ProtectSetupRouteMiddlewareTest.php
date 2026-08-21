<?php

declare(strict_types=1);

use App\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Session;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Session::flush();
    Settings::clearOverrides();
    Settings::override(['setup.is_installed' => false]);
});

test('2CF4Y-FR-MW8: redirects to 404 for setup routes when already installed', function () {
    Settings::override(['setup.is_installed' => true]);

    $this->get('/setup')->assertStatus(404);
});

test('2CF4Y-FR-MW8: allows access during finalization window after install', function () {
    Settings::override(['setup.is_installed' => true, 'setup.updated_at' => now()->toIso8601String()]);
    Session::put('setup.completed', true);

    $this->get('/setup')->assertStatus(200);
});

test('2CF4Y-FR-MW8: rejects invalid setup token with 403', function () {
    $response = $this->get('/setup?setup_token=invalid-token');

    expect($response->getStatusCode())->toBe(403);
});

test('2CF4Y-FR-MW8: rate limits after too many invalid token attempts', function () {
    for ($i = 0; $i < 20; $i++) {
        $this->get('/setup?setup_token=invalid-'.$i)->assertStatus(403);
    }

    $this->get('/setup?setup_token=invalid-20')->assertStatus(429);
});
