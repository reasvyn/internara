<?php

declare(strict_types=1);

use App\Domain\Setup\Services\SetupService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Pastikan status "belum terinstal" sebelum setiap tes
    if (File::exists(storage_path('app/.installed'))) {
        File::delete(storage_path('app/.installed'));
    }
});

afterAll(function () {
    // Bersihkan file instalasi jika ada yang tersisa
    if (File::exists(storage_path('app/.installed'))) {
        File::delete(storage_path('app/.installed'));
    }
});

test('it redirects uninstalled app to setup wizard', function () {
    $response = $this->get('/');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toBe(route('setup'));
});

test('it redirects login page to setup if not installed', function () {
    $response = $this->get('/login');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toBe(route('setup'));
});

test('it allows access to setup route when not installed', function () {
    $setupService = app(SetupService::class);
    $token = $setupService->generateToken();

    $response = $this->get('/setup?setup_token='.$token);

    $response->assertStatus(200);
});

test('it blocks setup route with 404 when already installed', function () {
    // Mock instalasi sukses
    File::put(storage_path('app/.installed'), json_encode(['installed_at' => now()]));

    $response = $this->get('/setup');

    $response->assertNotFound();
});

test('it allows normal routes when installed', function () {
    // Mock instalasi sukses
    File::put(storage_path('app/.installed'), json_encode(['installed_at' => now()]));

    $response = $this->get('/login');

    $response->assertStatus(200);
});
