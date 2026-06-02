<?php

declare(strict_types=1);

use App\Domain\Core\Support\CacheKeys;
use App\Domain\Settings\Support\Settings;
use App\Domain\Shared\Support\Theme;
use Illuminate\Support\Facades\Cache;

describe('Theme', function () {
    beforeEach(function () {
        Settings::clearOverrides();
        Cache::forget(CacheKeys::THEME_CSS_VARIABLES);
    });

    describe('defaults', function () {
        it('returns default colors from config', function () {
            $defaults = Theme::defaults();

            expect($defaults)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
            expect($defaults['primary'])->toBe('#059669');
            expect($defaults['base'])->toBe('#ffffff');
        });

        it('falls back to hardcoded defaults when config is missing', function () {
            config(['settings.colors.defaults' => null]);

            $defaults = Theme::defaults();

            expect($defaults['primary'])->toBe('#059669');
        });

        it('returns empty defaults when config returns non-array', function () {
            config(['settings.colors.defaults' => 'invalid']);

            $defaults = Theme::defaults();

            expect($defaults['primary'])->toBe('#059669');
        });
    });

    describe('presets', function () {
        it('returns color presets from config', function () {
            $presets = Theme::presets();

            expect($presets)->toHaveKeys(['sky', 'emerald', 'violet', 'rose', 'ocean', 'slate']);
        });
    });

    describe('all', function () {
        it('returns merged colors with defaults', function () {
            $colors = Theme::all();

            expect($colors)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
            expect($colors['primary'])->toBeString();
            expect($colors['base'])->toBeString();
        });

        it('respects Settings overrides', function () {
            Settings::override(['primary_color' => '#ff0000']);

            $colors = Theme::all();

            expect($colors['primary'])->toBe('#ff0000');
        });
    });

    describe('get', function () {
        it('returns specific color value', function () {
            expect(Theme::get('primary'))->toBeString();
            expect(Theme::get('primary'))->toMatch('/^#[0-9a-f]{6}$/');
        });

        it('falls back to default when key is not set', function () {
            expect(Theme::get('primary'))->toBe('#059669');
        });

        it('falls back to #000000 when key is unknown', function () {
            expect(Theme::get('nonexistent'))->toBe('#000000');
        });
    });

    describe('base', function () {
        it('returns base color', function () {
            $base = Theme::base();

            expect($base)->toBeString();
            expect($base)->toMatch('/^#[0-9a-f]{6}$/');
        });
    });

    describe('cssVariables', function () {
        it('returns light and dark variable maps', function () {
            $vars = Theme::cssVariables();

            expect($vars)->toHaveKeys(['light', 'dark']);
            expect($vars['light'])->toHaveKeys([
                '--color-base-100', '--color-base-200', '--color-base-300', '--color-base-content',
                '--color-primary', '--p', '--color-primary-content', '--pc',
                '--color-secondary', '--s', '--color-secondary-content', '--sc',
                '--color-accent', '--a', '--color-accent-content', '--ac',
            ]);
        });

        it('caches the result', function () {
            Theme::cssVariables();

            $cached = Cache::get(CacheKeys::THEME_CSS_VARIABLES);
            expect($cached)->toHaveKeys(['light', 'dark']);
        });

        it('returns same result from cache on second call', function () {
            $first = Theme::cssVariables();
            $second = Theme::cssVariables();

            expect($first)->toBe($second);
        });
    });
});
