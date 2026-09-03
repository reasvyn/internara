<?php

declare(strict_types=1);

use App\Modules\Core\Support\Color;

test('C8F0D-FR-SUP7: hexToRgb() converts a hex color to RGB channels', function () {
    expect(Color::hexToRgb('#ffffff'))->toBe([255, 255, 255]);
    expect(Color::hexToRgb('#ff0080'))->toBe([255, 0, 128]);
    expect(Color::hexToRgb('000000'))->toBe([0, 0, 0]);
});

test('C8F0D-FR-SUP7: rgbToHex() converts RGB channels to a hex color', function () {
    expect(Color::rgbToHex(255, 255, 255))->toBe('#ffffff');
    expect(Color::rgbToHex(255, 0, 128))->toBe('#ff0080');
});

test('C8F0D-FR-SUP7: relativeLuminance() is 1.0 for white and 0.0 for black', function () {
    expect(Color::relativeLuminance('#ffffff'))->toBe(1.0);
    expect(Color::relativeLuminance('#000000'))->toBe(0.0);
});

test('C8F0D-FR-SUP7: contrastColor() picks a dark tone for light backgrounds and a light tone for dark backgrounds', function () {
    expect(Color::contrastColor('#ffffff'))->toBe('#1a1a1a');
    expect(Color::contrastColor('#000000'))->toBe('#f0f0f0');
});

test('C8F0D-FR-SUP7: lighten() and darken() adjust channel brightness', function () {
    expect(Color::lighten('#000000', 10))->toBe('#1a1a1a');
    expect(Color::darken('#ffffff', 50))->toBe('#7f7f7f');
});

test('C8F0D-FR-SUP7: isValid() accepts six-digit hex with optional hash only', function () {
    expect(Color::isValid('#abc123'))->toBeTrue();
    expect(Color::isValid('abc123'))->toBeFalse();
    expect(Color::isValid('#12345'))->toBeFalse();
    expect(Color::isValid('#gggggg'))->toBeFalse();
});

test('C8F0D-FR-SUP7: computeBaseShades() builds daisyUI base shades from a light color', function () {
    $shades = Color::computeBaseShades('#ffffff');

    expect($shades['base100'])->toBe('#ffffff');
    expect($shades['base200'])->toBe('#f7f7f7');
    expect($shades['base300'])->toBe('#f0f0f0');
    expect($shades['content'])->toBe('#1a1a1a');
});

test('C8F0D-FR-SUP7: computeDarkShades() returns black shades for dark mode background', function () {
    $shades = Color::computeDarkShades('#ffffff');

    expect($shades['base100'])->toBe('#262626');
    expect($shades['base200'])->toBe('#171717');
    expect($shades['base300'])->toBe('#0a0a0a');
    expect($shades['content'])->toBe('#e5e5e5');
});
