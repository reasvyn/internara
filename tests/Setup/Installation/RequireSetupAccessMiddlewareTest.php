<?php

declare(strict_types=1);

use App\Settings\Services\Settings;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Session::flush();
    Cache::flush();
    Settings::clearOverrides();
    Settings::override(['setup.is_installed' => false]);
});

test('2CF4Y-FR-MW9: redirects to setup for non-setup routes when not installed', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(302);
    $response->assertRedirect('/setup');
});

test('2CF4Y-FR-MW9: allows access to setup route when not installed', function () {
    $response = $this->get('/setup');

    expect($response->getStatusCode())->toBe(200);
});

test('2CF4Y-FR-MW9: allows access to public assets when not installed', function () {
    $response = $this->get('/css/app.css');

    expect($response->getStatusCode())->toBe(404);
});

test('2CF4Y-FR-MW9: allows access when system is installed', function () {
    Settings::override(['setup.is_installed' => true]);
    Cache::forget(config('cache-keys.setup_installed'));
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/my-dashboard');

    expect($response->getStatusCode())->toBe(200);
});

test('2CF4Y-FR-MW9: allows Livewire requests when not installed', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->withHeaders(['X-Livewire' => 'true'])->get('/my-dashboard');

    expect($response->getStatusCode())->toBe(200);
});
