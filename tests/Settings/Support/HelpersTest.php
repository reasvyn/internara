<?php

declare(strict_types=1);

use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('setting function returns Settings instance when no key given', function () {
    $result = setting();

    expect($result)->toBeInstanceOf(App\Settings\Services\Settings::class);
});

test('setting function returns default when key not found', function () {
    $result = setting('nonexistent_key', 'fallback');

    expect($result)->toBe('fallback');
});

test('setting function returns stored value', function () {
    Setting::factory()->create(['key' => 'test_key', 'value' => 'test_value']);

    $result = setting('test_key');

    expect($result)->toBe('test_value');
});

test('brand function returns value', function () {
    $name = brand('name');

    expect($name)->toBeString();
    expect(strlen($name))->toBeGreaterThan(0);
});

test('brand function returns default for unknown key', function () {
    $result = brand('nonexistent_brand_key', 'fallback_brand');

    expect($result)->toBe('fallback_brand');
});
