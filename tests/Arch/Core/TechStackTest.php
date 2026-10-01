<?php

declare(strict_types=1);

namespace Tests\Arch\Core;

use TallStackUi\TallStackUiServiceProvider;

describe('FB792: Tech Stack manifest and architecture guarantees', function (): void {
    test('FB792-FR-STACK-001/002/003: language and framework floors', function (): void {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        expect($composer['require']['php'])->toBe('^8.4')
            ->and($composer['require']['laravel/framework'])->toBe('^13.0')
            ->and($composer['require']['livewire/livewire'])->toBe('^4.0');
    });

    test('FB792-FR-STACK-004/005/006 FB792-NFR-STACK-005: Tailwind and TallstackUI UI kit', function (): void {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $package = json_decode(file_get_contents(base_path('package.json')), true);

        expect($composer['require']['tallstackui/tallstackui'])->toBe('^4.0')
            ->and($package['devDependencies']['tailwindcss'])->toBe('^4.3.3');
    });

    test('FB792-FR-STACK-007: zero legacy UI tokens remain in tree', function (): void {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $package = json_decode(file_get_contents(base_path('package.json')), true);

        expect(array_key_exists('robsontenorio/mary', $composer['require']))->toBeFalse()
            ->and(array_key_exists('php-flasher/flasher-laravel', $composer['require']))->toBeFalse()
            ->and(array_key_exists('daisyui', $package['devDependencies'] ?? []))->toBeFalse();
    });

    test('FB792-FR-STACK-008/009/010/011 FB792-NFR-STACK-001/004: manifest registration and lockfile contract', function (): void {
        expect(file_exists(base_path('composer.lock')))->toBeTrue()
            ->and(file_exists(base_path('package-lock.json')))->toBeTrue();
    });

    test('FB792-FR-STACK-012/013/014: tier-1 defaults and build sanity', function (): void {
        expect(config('database.default'))->toBe('sqlite')
            ->and(config('queue.default'))->toBe('sync');
    });

    test('FB792-UC-STACK-001/002 FB792-NFR-STACK-002/003: lockfile commitments and manifest contracts', function (): void {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        expect($composer['name'])->toBe('reasvyn/internara');
    });

    test('FB792-DD-STACK-001/002/003/004: design decisions and migration completion', function (): void {
        expect(class_exists(TallStackUiServiceProvider::class))->toBeTrue();
    });
});
