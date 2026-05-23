<?php

declare(strict_types=1);

use App\Domain\Shared\Support\Theme;

describe('Theme', function () {
    it('returns defaults from config', function () {
        config(['settings.colors.defaults' => [
            'primary' => '#ff0000',
            'secondary' => '#00ff00',
        ]]);

        $defaults = Theme::defaults();

        expect($defaults['primary'])->toBe('#ff0000')
            ->and($defaults['secondary'])->toBe('#00ff00');
    });

    it('falls back to hardcoded defaults when config is not an array', function () {
        config(['settings.colors.defaults' => null]);

        $defaults = Theme::defaults();

        expect($defaults)->toHaveKey('primary')
            ->toHaveKey('secondary')
            ->toHaveKey('accent')
            ->toHaveKey('base')
            ->toHaveKey('content');
    });

    it('returns presets from config', function () {
        config(['settings.colors.presets' => ['ocean' => ['colors' => ['primary' => '#0000ff']]]]);

        $presets = Theme::presets();

        expect($presets)->toHaveKey('ocean');
    });

    it('returns empty array when no presets configured', function () {
        config(['settings.colors.presets' => []]);

        expect(Theme::presets())->toBe([]);
    });

    it('returns preset keys', function () {
        config(['settings.colors.presets' => ['ocean' => [], 'forest' => []]]);

        $keys = Theme::presetKeys();

        expect($keys)->toBe(['ocean', 'forest']);
    });

    it('returns all theme colors', function () {
        config(['settings.colors.defaults' => [
            'primary' => '#059669',
            'secondary' => '#6b7280',
            'accent' => '#f97316',
            'base' => '#ffffff',
            'content' => '#1a1a1a',
        ]]);

        $all = Theme::all();

        expect($all)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
    });

    it('gets a specific color value', function () {
        config(['settings.colors.defaults' => [
            'primary' => '#ff0000',
            'secondary' => '#6b7280',
            'accent' => '#f97316',
            'base' => '#ffffff',
            'content' => '#1a1a1a',
        ]]);

        expect(Theme::get('primary'))->toBe('#ff0000');
    });

    it('returns fallback for missing color', function () {
        config(['settings.colors.defaults' => [
            'primary' => '#059669',
            'secondary' => '#6b7280',
            'accent' => '#f97316',
            'base' => '#ffffff',
            'content' => '#1a1a1a',
        ]]);

        expect(Theme::get('nonexistent'))->toBe('#000000');
    });

    it('returns base color', function () {
        config(['settings.colors.defaults' => [
            'primary' => '#059669',
            'secondary' => '#6b7280',
            'accent' => '#f97316',
            'base' => '#f0f0f0',
            'content' => '#1a1a1a',
        ]]);

        expect(Theme::base())->toBe('#f0f0f0');
    });

    it('returns css variables structure', function () {
        config(['settings.colors.defaults' => [
            'primary' => '#059669',
            'secondary' => '#6b7280',
            'accent' => '#f97316',
            'base' => '#ffffff',
            'content' => '#1a1a1a',
        ]]);

        $css = Theme::cssVariables();

        expect($css)->toHaveKeys(['light', 'dark'])
            ->and($css['light'])->toHaveKey('--color-base-100')
            ->and($css['dark'])->toHaveKey('--color-base-100');
    });

    it('is a final class', function () {
        $ref = new ReflectionClass(Theme::class);

        expect($ref->isFinal())->toBeTrue();
    });
});
