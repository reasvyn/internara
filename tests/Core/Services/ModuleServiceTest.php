<?php

declare(strict_types=1);

use App\Core\Services\ModuleService;

test('can instantiate module service', function () {
    $service = app(ModuleService::class);

    expect($service)->toBeInstanceOf(ModuleService::class);
});

test('discovers livewire components without error', function () {
    $service = app(ModuleService::class);

    expect(fn () => $service->discoverLivewireComponents())->not->toThrow(\Throwable::class);
});

test('discovers policies without error', function () {
    $service = app(ModuleService::class);

    expect(fn () => $service->discoverPolicies())->not->toThrow(\Throwable::class);
});

test('registers blade namespaces without error', function () {
    $service = app(ModuleService::class);

    expect(fn () => $service->registerBladeNamespaces())->not->toThrow(\Throwable::class);
});
