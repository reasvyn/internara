<?php

declare(strict_types=1);

use App\Core\Support\Color;

test('hexToRgb parses hex color correctly', function () {
    expect(Color::hexToRgb('#ff0000'))->toEqual([255, 0, 0]);
    expect(Color::hexToRgb('#00ff00'))->toEqual([0, 255, 0]);
    expect(Color::hexToRgb('#0000ff'))->toEqual([0, 0, 255]);
    expect(Color::hexToRgb('#ffffff'))->toEqual([255, 255, 255]);
    expect(Color::hexToRgb('#000000'))->toEqual([0, 0, 0]);
});

test('hexToRgb handles hex without hash', function () {
    expect(Color::hexToRgb('ff0000'))->toEqual([255, 0, 0]);
});

test('relativeLuminance returns expected values', function () {
    expect(Color::relativeLuminance('#000000'))->toBe(0.0);
    expect(Color::relativeLuminance('#ffffff'))->toBe(1.0);
    expect(Color::relativeLuminance('#ff0000'))->toBe(0.299);
    expect(round(Color::relativeLuminance('#00ff00'), 3))->toBe(0.587);
    expect(round(Color::relativeLuminance('#0000ff'), 3))->toBe(0.114);
});

test('contrastColor returns dark for light colors', function () {
    expect(Color::contrastColor('#ffffff'))->toBe('#1a1a1a');
    expect(Color::contrastColor('#ffff00'))->toBe('#1a1a1a');
});

test('contrastColor returns light for dark colors', function () {
    expect(Color::contrastColor('#000000'))->toBe('#f0f0f0');
    expect(Color::contrastColor('#0000ff'))->toBe('#f0f0f0');
});

test('lighten lightens color by given percent', function () {
    expect(Color::lighten('#000000', 50))->toBe('#808080');
    expect(Color::lighten('#ff0000', 100))->toBe('#ffffff');
    expect(Color::lighten('#888888', 0))->toBe('#888888');
});

test('darken darkens color by given percent', function () {
    expect(Color::darken('#ffffff', 100))->toBe('#000000');
    expect(Color::darken('#888888', 0))->toBe('#888888');
    expect(Color::darken('#ffffff', 50))->not->toBe('#ffffff');
    expect(Color::darken('#888888', 50))->not->toBe('#888888');
});

test('isValid validates hex colors', function () {
    expect(Color::isValid('#ffffff'))->toBeTrue();
    expect(Color::isValid('#000000'))->toBeTrue();
    expect(Color::isValid('#ABC123'))->toBeTrue();
    expect(Color::isValid('#abc'))->toBeFalse();
    expect(Color::isValid('ffffff'))->toBeFalse();
    expect(Color::isValid('#gggggg'))->toBeFalse();
    expect(Color::isValid(''))->toBeFalse();
});

test('computeBaseShades returns light shades for light colors', function () {
    $shades = Color::computeBaseShades('#ffffff');

    expect($shades)->toHaveKey('base100', '#ffffff');
    expect($shades)->toHaveKey('content', '#1a1a1a');
});

test('computeBaseShades returns dark shades for dark colors', function () {
    $shades = Color::computeBaseShades('#000000');

    expect($shades)->toHaveKey('base100', '#000000');
    expect($shades)->toHaveKey('content', '#f0f0f0');
});

test('computeDarkShades returns darker variant', function () {
    $shades = Color::computeDarkShades('#ffffff');

    expect($shades)->toHaveKey('base100');
    expect($shades)->toHaveKey('content', '#e5e5e5');
    expect($shades['base100'])->not->toBe('#ffffff');
});

test('Color class is final', function () {
    $ref = new ReflectionClass(Color::class);
    expect($ref->isFinal())->toBeTrue();
});
