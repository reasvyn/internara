<?php

declare(strict_types=1);

use App\Settings\Theme\Support\Theme;

test('defaults returns array with correct keys', function () {
    $defaults = Theme::defaults();

    expect($defaults)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
    expect($defaults['primary'])->toMatch('/^#[0-9A-Fa-f]{6}$/');
});

test('presets returns array of presets', function () {
    $presets = Theme::presets();

    expect($presets)->toBeArray();
    expect($presets)->toHaveKeys(['sky', 'emerald', 'violet', 'rose', 'ocean', 'slate']);
});

test('all returns all theme colors', function () {
    $colors = Theme::all();

    expect($colors)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
    foreach ($colors as $color) {
        expect($color)->toMatch('/^#[0-9A-Fa-f]{6}$/');
    }
});

test('get returns specific color', function () {
    $primary = Theme::get('primary');

    expect($primary)->toMatch('/^#[0-9A-Fa-f]{6}$/');
});

test('get returns default for unknown key', function () {
    expect(Theme::get('nonexistent'))->toBe('#000000');
});

test('base returns base color', function () {
    $base = Theme::base();

    expect($base)->toMatch('/^#[0-9A-Fa-f]{6}$/');
});

test('cssVariables returns light and dark arrays', function () {
    $vars = Theme::cssVariables();

    expect($vars)->toHaveKeys(['light', 'dark']);
    expect($vars['light'])->toHaveKey('--color-primary');
    expect($vars['dark'])->toHaveKey('--color-primary');
    expect($vars['light'])->toHaveKey('--color-base-100');
    expect($vars['dark'])->toHaveKey('--color-base-100');
});
