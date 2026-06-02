<?php

declare(strict_types=1);

use App\Domain\Settings\Support\Color;

describe('Color', function () {
    describe('hexToRgb', function () {
        it('converts hex to RGB array', function () {
            expect(Color::hexToRgb('#ff0000'))->toBe([255, 0, 0]);
            expect(Color::hexToRgb('#00ff00'))->toBe([0, 255, 0]);
            expect(Color::hexToRgb('#0000ff'))->toBe([0, 0, 255]);
            expect(Color::hexToRgb('#ffffff'))->toBe([255, 255, 255]);
            expect(Color::hexToRgb('#000000'))->toBe([0, 0, 0]);
        });

        it('strips hash prefix', function () {
            expect(Color::hexToRgb('ff0000'))->toBe([255, 0, 0]);
        });

        it('handles mixed case', function () {
            expect(Color::hexToRgb('#FFAABB'))->toBe([255, 170, 187]);
        });
    });

    describe('relativeLuminance', function () {
        it('returns 1.0 for white', function () {
            expect(Color::relativeLuminance('#ffffff'))->toBe(1.0);
        });

        it('returns 0.0 for black', function () {
            expect(Color::relativeLuminance('#000000'))->toBe(0.0);
        });

        it('returns value between 0 and 1 for gray', function () {
            $luminance = Color::relativeLuminance('#808080');
            expect($luminance)->toBeGreaterThan(0);
            expect($luminance)->toBeLessThan(1);
        });
    });

    describe('contrastColor', function () {
        it('returns dark color for light backgrounds', function () {
            expect(Color::contrastColor('#ffffff'))->toBe('#1a1a1a');
            expect(Color::contrastColor('#f0f0f0'))->toBe('#1a1a1a');
        });

        it('returns light color for dark backgrounds', function () {
            expect(Color::contrastColor('#000000'))->toBe('#f0f0f0');
            expect(Color::contrastColor('#1a1a1a'))->toBe('#f0f0f0');
        });
    });

    describe('lighten', function () {
        it('lightens color by given percent', function () {
            expect(Color::lighten('#000000', 50))->toBe('#808080');
            expect(Color::lighten('#ff0000', 50))->toBe('#ff8080');
        });

        it('caps at 255', function () {
            expect(Color::lighten('#ffffff', 50))->toBe('#ffffff');
        });

        it('does not overflow near max', function () {
            expect(Color::lighten('#fefefe', 1))->not->toBe('#ffffff');
        });

        it('handles zero percent', function () {
            expect(Color::lighten('#059669', 0))->toBe('#059669');
        });
    });

    describe('darken', function () {
        it('darkens color by given percent', function () {
            expect(Color::darken('#ffffff', 50))->toBe('#7f7f7f');
            expect(Color::darken('#00ff00', 50))->toBe('#007f00');
        });

        it('floors at 0', function () {
            expect(Color::darken('#000000', 50))->toBe('#000000');
        });

        it('handles zero percent', function () {
            expect(Color::darken('#059669', 0))->toBe('#059669');
        });
    });

    describe('isValid', function () {
        it('accepts valid 6-digit hex colors', function () {
            expect(Color::isValid('#ff0000'))->toBeTrue();
            expect(Color::isValid('#FFFFFF'))->toBeTrue();
            expect(Color::isValid('#059669'))->toBeTrue();
        });

        it('rejects invalid formats', function () {
            expect(Color::isValid('#fff'))->toBeFalse();
            expect(Color::isValid('#gggggg'))->toBeFalse();
            expect(Color::isValid('ff0000'))->toBeFalse();
            expect(Color::isValid('red'))->toBeFalse();
            expect(Color::isValid(''))->toBeFalse();
        });
    });

    describe('computeBaseShades', function () {
        it('darkens light base colors', function () {
            $shades = Color::computeBaseShades('#ffffff');

            expect($shades['base100'])->toBe('#ffffff');
            expect($shades['base200'])->not->toBe($shades['base100']);
            expect($shades['base300'])->not->toBe($shades['base200']);
            expect($shades['content'])->toBe('#1a1a1a');
        });

        it('lightens dark base colors', function () {
            $shades = Color::computeBaseShades('#1a1a1a');

            expect($shades['base100'])->toBe('#1a1a1a');
            expect($shades['base200'])->not->toBe($shades['base100']);
            expect($shades['base300'])->not->toBe($shades['base200']);
            expect($shades['content'])->toBe('#f0f0f0');
        });
    });

    describe('computeDarkShades', function () {
        it('darkens light base significantly for dark mode', function () {
            $shades = Color::computeDarkShades('#ffffff');

            expect($shades['base100'])->not->toBe('#ffffff');
            expect($shades['base100'])->toMatch('/^#[0-9a-f]{6}$/');
            expect($shades['content'])->toBe('#e5e5e5');
        });

        it('keeps dark base as base100 when already dark', function () {
            $shades = Color::computeDarkShades('#1a1a1a');

            expect($shades['base100'])->toBe('#1a1a1a');
            expect($shades['content'])->toBe('#e5e5e5');
        });
    });
});
