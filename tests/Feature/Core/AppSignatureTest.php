<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domain\Core\Support\AppInfo;
use App\Livewire\Core\AppSignature;
use Livewire\Livewire;

beforeEach(function () {
    AppInfo::clearCache();
});

afterEach(function () {
    AppInfo::clearCache();
});

test('app signature renders with author information', function () {
    $author = AppInfo::get('author.name');
    $version = AppInfo::version();
    $name = AppInfo::get('name');

    Livewire::test(AppSignature::class)
        ->assertSee($name)
        ->assertSee($version)
        ->assertSee($author)
        ->assertSee(AppInfo::get('license'));
});

test('app signature renders github link when available', function () {
    $github = AppInfo::get('author.github');
    if ($github) {
        Livewire::test(AppSignature::class)->assertSee($github);
    }
});
