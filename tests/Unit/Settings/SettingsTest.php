<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Settings\Support\Settings;

test('settings can set and clear runtime overrides', function () {
    Settings::override(['app_theme' => 'dark', 'max_users' => 50]);

    expect(Settings::get('app_theme'))->toBe('dark');
    expect(Settings::get('max_users'))->toBe(50);

    Settings::clearOverrides();

    expect(Settings::get('app_theme'))->toBeNull();
});

test('settings resolves fallbacks correctly', function () {
    expect(Settings::get('non_existent_key', 'fallback_val'))->toBe('fallback_val');
});
