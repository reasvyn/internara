<?php

declare(strict_types=1);

use App\Core\Services\ModuleDiscoverService;

test('can instantiate module discover service', function () {
    $service = app(ModuleDiscoverService::class);

    expect($service)->toBeInstanceOf(ModuleDiscoverService::class);
});

test('discovers livewire components without error', function () {
    $service = app(ModuleDiscoverService::class);

    expect(fn () => $service->discoverLivewireComponents())->not->toThrow(\Throwable::class);
});

test('discovers policies without error', function () {
    $service = app(ModuleDiscoverService::class);

    expect(fn () => $service->discoverPolicies())->not->toThrow(\Throwable::class);
});
