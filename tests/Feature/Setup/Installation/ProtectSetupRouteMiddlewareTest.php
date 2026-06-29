<?php

declare(strict_types=1);

use App\Settings\Livewire\LangSwitcher;
use App\Settings\Livewire\ThemeSwitcher;
use App\Settings\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Livewire::component('settings.theme-switcher', ThemeSwitcher::class);
    Livewire::component('settings.lang-switcher', LangSwitcher::class);

    Route::get('/_test_setup_protect', function () {
        return 'ok';
    })->middleware('setup.protected');
});

test('blocks access without token when system is installed', function () {
    Settings::set([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
    ]);
    Cache::flush();

    $response = $this->get('/_test_setup_protect');

    $response->assertStatus(404);
});

test('allows access when session has completed flag', function () {
    Settings::set([
        'setup.is_installed' => ['value' => true, 'group' => 'setup', 'type' => 'boolean'],
        'setup.updated_at' => [
            'value' => now()->toIso8601String(),
            'group' => 'setup',
            'type' => 'datetime',
        ],
    ]);
    Cache::flush();
    Session::put('setup.completed', true);

    $response = $this->get('/_test_setup_protect');

    $response->assertStatus(200);
});

test('shows token entry form when system is not installed and no token', function () {
    Settings::set([
        'setup.is_installed' => ['value' => false, 'group' => 'setup', 'type' => 'boolean'],
    ]);
    Cache::flush();

    $response = $this->get('/_test_setup_protect');

    $response->assertStatus(200);
});
