<?php

declare(strict_types=1);

use App\Core\Support\Color;

describe('hexToRgb', function () {
    it('converts hex to rgb array', function () {
        expect(Color::hexToRgb('#ff0000'))->toBe([255, 0, 0]);
        expect(Color::hexToRgb('#00ff00'))->toBe([0, 255, 0]);
        expect(Color::hexToRgb('#0000ff'))->toBe([0, 0, 255]);
        expect(Color::hexToRgb('#ffffff'))->toBe([255, 255, 255]);
        expect(Color::hexToRgb('#000000'))->toBe([0, 0, 0]);
    });

    it('handles hex without hash', function () {
        expect(Color::hexToRgb('ff0000'))->toBe([255, 0, 0]);
    });
});

describe('relativeLuminance', function () {
    it('returns 1.0 for white', function () {
        expect(Color::relativeLuminance('#ffffff'))->toBe(1.0);
    });

    it('returns 0.0 for black', function () {
        expect(Color::relativeLuminance('#000000'))->toBe(0.0);
    });

    it('returns value between 0 and 1 for midtones', function () {
        $luminance = Color::relativeLuminance('#ff8800');

        expect($luminance)->toBeGreaterThan(0);
        expect($luminance)->toBeLessThan(1);
    });
});

describe('contrastColor', function () {
    it('returns dark for light backgrounds', function () {
        expect(Color::contrastColor('#ffffff'))->toBe('#1a1a1a');
    });

    it('returns light for dark backgrounds', function () {
        expect(Color::contrastColor('#000000'))->toBe('#f0f0f0');
    });
});

describe('lighten', function () {
    it('lightens a dark color', function () {
        $result = Color::lighten('#000000', 50);

        expect(Color::hexToRgb($result))->toBe([128, 128, 128]);
    });

    it('does not exceed 255', function () {
        $result = Color::lighten('#ffffff', 50);

        expect($result)->toBe('#ffffff');
    });
});

describe('darken', function () {
    it('darkens a light color', function () {
        $result = Color::darken('#ffffff', 50);

        expect(Color::hexToRgb($result))->toBe([127, 127, 127]);
    });

    it('does not go below 0', function () {
        $result = Color::darken('#000000', 50);

        expect($result)->toBe('#000000');
    });
});

describe('isValid', function () {
    it('validates correct hex colors', function () {
        expect(Color::isValid('#ff0000'))->toBeTrue();
        expect(Color::isValid('#000000'))->toBeTrue();
        expect(Color::isValid('#FFFFFF'))->toBeTrue();
    });

    it('rejects invalid hex colors', function () {
        expect(Color::isValid('#fff'))->toBeFalse();
        expect(Color::isValid('ff0000'))->toBeFalse();
        expect(Color::isValid('#gggggg'))->toBeFalse();
    });
});

describe('computeBaseShades', function () {
    it('darkens light colors and uses dark text', function () {
        $shades = Color::computeBaseShades('#ffffff');

        expect($shades['base100'])->toBe('#ffffff');
        expect($shades['content'])->toBe('#1a1a1a');
    });

    it('lightens dark colors and uses light text', function () {
        $shades = Color::computeBaseShades('#000000');

        expect($shades['base100'])->toBe('#000000');
        expect($shades['content'])->toBe('#f0f0f0');
    });
});

describe('computeDarkShades', function () {
    it('computes dark shades for light input', function () {
        $shades = Color::computeDarkShades('#ffffff');

        expect($shades['base100'])->not->toBe('#ffffff');
        expect($shades['content'])->toBe('#e5e5e5');
    });
});
