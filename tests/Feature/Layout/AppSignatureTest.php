<?php

declare(strict_types=1);

namespace Tests\Feature\Layout;

use App\Livewire\Layout\AppSignature;
use App\Support\AppInfo;
use Livewire\Livewire;

beforeEach(function () {
    AppInfo::clearCache();
});

afterEach(function () {
    AppInfo::clearCache();
});

test('app signature renders with author information', function () {
    Livewire::test(AppSignature::class)
        ->assertSee('Internara')
        ->assertSee(AppInfo::version())
        ->assertSee('Reas Vyn')
        ->assertSee('MIT');
});

test('app signature renders github link when available', function () {
    Livewire::test(AppSignature::class)
        ->assertSee('https://github.com/reasvyn');
});

test('app signature handles missing author gracefully', function () {
    $original = AppInfo::all();

    $temp = [
        'name' => 'Test App',
        'version' => '1.0.0',
        'license' => 'MIT',
    ];

    $path = base_path('app_info.json');
    file_put_contents($path, json_encode($temp));
    AppInfo::clearCache();

    Livewire::test(AppSignature::class)
        ->assertSee('Test App')
        ->assertSee('1.0.0')
        ->assertDontSee('Reas Vyn');

    file_put_contents($path, json_encode($original));
    AppInfo::clearCache();
});
