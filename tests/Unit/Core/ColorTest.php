<?php

declare(strict_types=1);

use App\Modules\Core\Support\Color;

describe('C8F0D: Color helper', function (): void {
    test('C8F0D-FR-UTIL-007: hexToRgb converts with or without the hash mark', function (): void {
        expect(Color::hexToRgb('#ffffff'))->toBe([255, 255, 255]);
        expect(Color::hexToRgb('000000'))->toBe([0, 0, 0]);
        expect(Color::hexToRgb('#10b981'))->toBe([16, 185, 129]);
    });

    test('C8F0D-FR-UTIL-007: rgbToHex formats zero-padded lowercase hex', function (): void {
        expect(Color::rgbToHex(255, 255, 255))->toBe('#ffffff');
        expect(Color::rgbToHex(0, 0, 0))->toBe('#000000');
        expect(Color::rgbToHex(16, 185, 129))->toBe('#10b981');
    });

    test('C8F0D-FR-UTIL-007: relativeLuminance spans black to white', function (): void {
        expect(Color::relativeLuminance('#000000'))->toBe(0.0);
        expect(Color::relativeLuminance('#ffffff'))->toBe(1.0);

        $mid = Color::relativeLuminance('#808080');

        expect($mid)->toBeGreaterThan(0.0);
        expect($mid)->toBeLessThan(1.0);
    });

    test('C8F0D-FR-UTIL-007: contrastColor picks dark content on light colors and vice versa', function (): void {
        expect(Color::contrastColor('#ffffff'))->toBe('#1a1a1a');
        expect(Color::contrastColor('#000000'))->toBe('#f0f0f0');
        expect(Color::contrastColor('#10b981'))->toBe('#1a1a1a');
        expect(Color::contrastColor('#111111'))->toBe('#f0f0f0');
    });

    test('C8F0D-FR-UTIL-007: lighten and darken reach the extremes at full percent', function (): void {
        expect(Color::lighten('#000000', 100))->toBe('#ffffff');
        expect(Color::darken('#ffffff', 100))->toBe('#000000');
        expect(Color::lighten('#000000', 0))->toBe('#000000');
        expect(Color::darken('#ffffff', 0))->toBe('#ffffff');
    });

    test('C8F0D-FR-UTIL-007: computeBaseShades pairs light bases with dark content', function (): void {
        $shades = Color::computeBaseShades('#f5f5f5');

        expect($shades['base100'])->toBe('#f5f5f5');
        expect($shades['content'])->toBe('#1a1a1a');
        expect(Color::relativeLuminance($shades['base200']))->toBeLessThan(Color::relativeLuminance($shades['base100']));
        expect(Color::relativeLuminance($shades['base300']))->toBeLessThan(Color::relativeLuminance($shades['base200']));
    });

    test('C8F0D-FR-UTIL-007: computeBaseShades pairs dark bases with light content', function (): void {
        $shades = Color::computeBaseShades('#111111');

        expect($shades['base100'])->toBe('#111111');
        expect($shades['content'])->toBe('#f0f0f0');
        expect(Color::relativeLuminance($shades['base200']))->toBeGreaterThan(Color::relativeLuminance($shades['base100']));
        expect(Color::relativeLuminance($shades['base300']))->toBeGreaterThan(Color::relativeLuminance($shades['base200']));
    });

    test('C8F0D-FR-UTIL-007: computeDarkShades returns the fixed dark palette', function (): void {
        $shades = Color::computeDarkShades('#10b981');

        expect($shades)->toBe([
            'base100' => '#262626',
            'base200' => '#171717',
            'base300' => '#0a0a0a',
            'content' => '#e5e5e5',
        ]);
    });

    test('isValid accepts six-digit hex and rejects anything else', function (): void {
        expect(Color::isValid('#10b981'))->toBeTrue();
        expect(Color::isValid('#ABCDEF'))->toBeTrue();
        expect(Color::isValid('10b981'))->toBeFalse();
        expect(Color::isValid('#abc'))->toBeFalse();
        expect(Color::isValid('#gggggg'))->toBeFalse();
        expect(Color::isValid(''))->toBeFalse();
    });

    test('tailwindScale anchors 500 on the input with a lighter 50 and darker 950', function (): void {
        $scale = Color::tailwindScale('#10b981');

        expect(array_keys($scale))->toBe([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]);
        expect($scale['500'])->toBe('#10b981');
        expect(Color::relativeLuminance($scale['50']))->toBeGreaterThan(Color::relativeLuminance($scale['500']));
        expect(Color::relativeLuminance($scale['950']))->toBeLessThan(Color::relativeLuminance($scale['500']));
        expect(Color::relativeLuminance($scale['100']))->toBeGreaterThan(Color::relativeLuminance($scale['200']));
        expect(Color::relativeLuminance($scale['800']))->toBeGreaterThan(Color::relativeLuminance($scale['900']));
    });
});
