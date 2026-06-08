<?php

declare(strict_types=1);

use App\Core\Support\CacheKeys;

test('cache keys are strings', function () {
    $ref = new ReflectionClass(CacheKeys::class);

    foreach ($ref->getConstants() as $name => $value) {
        expect($value)->toBeString("Constant {$name} must be a string");
    }
});

test('cache keys follow naming convention', function () {
    $ref = new ReflectionClass(CacheKeys::class);

    foreach ($ref->getConstants() as $name => $value) {
        expect($value)->toMatch(
            '/^[a-z_][a-z0-9_.:-]*$/',
            "Key {$name} value '{$value}' does not match naming convention",
        );
    }
});

test('cache keys have unique values', function () {
    $ref = new ReflectionClass(CacheKeys::class);
    $values = $ref->getConstants();

    expect(array_unique($values))->toHaveCount(count($values));
});

test('setup installed key is defined', function () {
    expect(CacheKeys::SETUP_INSTALLED)->toBe('setup.is_installed');
});

test('admin dashboard stats key is defined', function () {
    expect(CacheKeys::ADMIN_DASHBOARD_STATS)->toBe('sysadmin.dashboard.stats');
});

test('theme css variables key is defined', function () {
    expect(CacheKeys::THEME_CSS_VARIABLES)->toBe('theme.css_variables');
});

test('notification unread key uses placeholder', function () {
    expect(CacheKeys::NOTIFICATION_UNREAD)->toBe('notification.unread:');
});

test('core integrity key is defined', function () {
    expect(CacheKeys::CORE_INTEGRITY)->toBe('core.integrity_verified');
});

test('module discovery keys are defined', function () {
    expect(CacheKeys::MODULE_LIVEWIRE)->toBe('module.discovered_livewire');
    expect(CacheKeys::MODULE_POLICIES)->toBe('module.discovered_policies');
    expect(CacheKeys::MODULE_VIEWS)->toBe('module.discovered_views');
});

test('auth login failures key uses placeholder', function () {
    expect(CacheKeys::AUTH_LOGIN_FAILURES)->toBe('auth.login-failures:');
});

test('health check key is defined', function () {
    expect(CacheKeys::HEALTH_CHECK)->toBe('health_check');
});

test('settings keys are defined', function () {
    expect(CacheKeys::SETTINGS_ALL)->toBe('settings.all');
    expect(CacheKeys::SETTINGS_GROUP)->toBe('settings.group.');
    expect(CacheKeys::SETTINGS_KEYS)->toBe('settings.keys');
    expect(CacheKeys::SETTINGS_KEY)->toBe('settings.');
});
