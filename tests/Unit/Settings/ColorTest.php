<?php

declare(strict_types=1);

use App\Domain\Settings\Support\Color;

describe('Color helper', function () {
    it('converts hex to RGB', function () {
        $rgb = Color::hexToRgb('#ff0000');

        expect($rgb)->toBe([255, 0, 0]);
    });

    it('computes relative luminance', function () {
        expect(Color::relativeLuminance('#ffffff'))->toBeGreaterThan(0.9)
            ->and(Color::relativeLuminance('#000000'))->toBeLessThan(0.01);
    });

    it('returns light contrast for dark colors', function () {
        expect(Color::contrastColor('#000000'))->toBe('#f0f0f0');
    });

    it('returns dark contrast for light colors', function () {
        expect(Color::contrastColor('#ffffff'))->toBe('#1a1a1a');
    });

    it('lightens colors', function () {
        $lightened = Color::lighten('#000000', 50);

        expect($lightened)->toStartWith('#');
    });

    it('darkens colors', function () {
        $darkened = Color::darken('#ffffff', 50);

        expect($darkened)->toBe('#7f7f7f');
    });

    it('validates hex colors', function () {
        expect(Color::isValid('#ff0000'))->toBeTrue()
            ->and(Color::isValid('#fff'))->toBeFalse()
            ->and(Color::isValid('red'))->toBeFalse();
    });

    it('computes base shades for light backgrounds', function () {
        $shades = Color::computeBaseShades('#ffffff');

        expect($shades)->toHaveKeys(['base100', 'base200', 'base300', 'content'])
            ->and($shades['content'])->toBe('#1a1a1a');
    });

    it('computes base shades for dark backgrounds', function () {
        $shades = Color::computeBaseShades('#1a1a1a');

        expect($shades['content'])->toBe('#f0f0f0');
    });
});
