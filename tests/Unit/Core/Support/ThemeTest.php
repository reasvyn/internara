<?php

declare(strict_types=1);

use App\Core\Support\Color;
use App\Core\Support\Theme;

test('Theme returns default colors when settings store is empty', function () {
    $defaults = Theme::defaults();
    expect(Theme::get('primary'))->toBe($defaults['primary']);
});

test('Theme returns presets as array', function () {
    expect(Theme::presets())->toBeArray();
});

test('Theme resolves css variables properly', function () {
    $vars = Theme::cssVariables();
    expect($vars)->toBeArray();
    expect($vars)->toHaveKeys(['light', 'dark']);
});

test('Color utility computes contrast colors correctly', function () {
    // Contrast color of black should be #f0f0f0
    expect(Color::contrastColor('#000000'))->toBe('#f0f0f0');
    // Contrast color of white should be #1a1a1a
    expect(Color::contrastColor('#ffffff'))->toBe('#1a1a1a');

    // Lighten black should make it lighter
    expect(Color::lighten('#000000', 50))->toBe('#808080');
});
