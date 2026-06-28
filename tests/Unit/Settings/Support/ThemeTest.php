<?php

declare(strict_types=1);

use App\Settings\Theme\Support\Theme;

test('returns default colors', function () {
    $defaults = Theme::defaults();

    expect($defaults)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
    expect($defaults['primary'])->toMatch('/^#[0-9a-f]{6}$/');
});

test('returns presets as array', function () {
    $presets = Theme::presets();

    expect($presets)->toBeArray();
});

test('all returns color array with required keys', function () {
    $colors = Theme::all();

    expect($colors)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
});

test('get returns specific color key', function () {
    $primary = Theme::get('primary');

    expect($primary)->toMatch('/^#[0-9a-f]{6}$/');
});

test('get returns fallback for unknown key', function () {
    $color = Theme::get('nonexistent');

    expect($color)->toBe('#000000');
});
