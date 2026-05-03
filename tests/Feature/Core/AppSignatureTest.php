<?php

declare(strict_types=1);

namespace Tests\Feature\Layout;

use App\Domain\Core\Support\AppInfo;
use App\Livewire\Layout\AppSignature;
use Livewire\Livewire;

beforeEach(function () {
    AppInfo::clearCache();
});

afterEach(function () {
    AppInfo::clearCache();
});

test('app signature renders with author information', function () {
    Livewire::test(AppSignature::class)
        ->assertSee('internara')
        ->assertSee(AppInfo::version())
        ->assertSee('Reas Vyn')
        ->assertSee('MIT');
});

test('app signature renders github link when available', function () {
    Livewire::test(AppSignature::class)->assertSee('https://github.com/reasvyn');
});
