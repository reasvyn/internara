<?php

declare(strict_types=1);

use App\Domain\Settings\Support\Settings;

beforeEach(function () {
    Settings::clearOverrides();
});

describe('Settings helper', function () {
    it('resolves from AppInfo for mapped keys', function () {
        expect(Settings::get('app_name'))->toBeString();
    });

    it('returns default for missing key', function () {
        expect(Settings::get('nonexistent_key', 'fallback'))->toBe('fallback');
    });

    it('uses runtime overrides', function () {
        Settings::override(['test_override' => 'overridden']);
        expect(Settings::get('test_override'))->toBe('overridden');
    });

    it('returns false for has when key is missing', function () {
        expect(Settings::has('definitely_missing'))->toBeFalse();
    });

    it('returns array for array key input', function () {
        $result = Settings::get(['app_name', 'nonexistent']);
        expect($result)->toHaveKeys(['app_name', 'nonexistent']);
    });

    it('returns settings from config fallback', function () {
        config(['app.debug' => true]);
        expect(Settings::get('app.debug'))->toBeTrue();
    });
});
