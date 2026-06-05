<?php

declare(strict_types=1);

use App\Core\Support\CacheKeys;

test('Setup installed key is correct', function () {
    expect(CacheKeys::SETUP_INSTALLED)->toBe('setup.is_installed');
});

test('Admin dashboard stats key is correct', function () {
    expect(CacheKeys::ADMIN_DASHBOARD_STATS)->toBe('sysadmin.dashboard.stats');
});

test('Theme CSS variables key is correct', function () {
    expect(CacheKeys::THEME_CSS_VARIABLES)->toBe('theme.css_variables');
});

test('Notification unread key pattern is correct', function () {
    expect(CacheKeys::NOTIFICATION_UNREAD)->toBe('notification.unread:');
});

test('Core integrity key is correct', function () {
    expect(CacheKeys::CORE_INTEGRITY)->toBe('core.integrity_verified');
});

test('Core app name key is correct', function () {
    expect(CacheKeys::CORE_APP_NAME)->toBe('core.app_name');
});

test('AppInfo metadata key is correct', function () {
    expect(CacheKeys::APPINFO_METADATA)->toBe('appinfo.metadata');
});

test('Module discovery keys are correct', function () {
    expect(CacheKeys::DOMAIN_LIVEWIRE)->toBe('domain.discovered_livewire');
    expect(CacheKeys::DOMAIN_POLICIES)->toBe('domain.discovered_policies');
    expect(CacheKeys::DOMAIN_VIEWS)->toBe('domain.discovered_views');
});

test('Auth login failures key pattern is correct', function () {
    expect(CacheKeys::AUTH_LOGIN_FAILURES)->toBe('auth.login-failures:');
});

test('Health check key is correct', function () {
    expect(CacheKeys::HEALTH_CHECK)->toBe('health_check');
});

test('Recover admin attempts key pattern is correct', function () {
    expect(CacheKeys::RECOVER_ADMIN_ATTEMPTS)->toBe('recover_admin_attempts_');
});

test('Settings keys are correct', function () {
    expect(CacheKeys::SETTINGS_ALL)->toBe('settings.all');
    expect(CacheKeys::SETTINGS_GROUP)->toBe('settings.group.');
    expect(CacheKeys::SETTINGS_KEYS)->toBe('settings.keys');
    expect(CacheKeys::SETTINGS_KEY)->toBe('settings.');
});

test('CacheKeys class is final and readonly', function () {
    $ref = new ReflectionClass(CacheKeys::class);
    expect($ref->isFinal())->toBeTrue();
    expect($ref->isReadOnly())->toBeTrue();
});

test('CacheKeys constants are strings', function () {
    $ref = new ReflectionClass(CacheKeys::class);
    $constants = $ref->getReflectionConstants();

    foreach ($constants as $const) {
        if (! $const->isPublic()) {
            continue;
        }

        expect($const->getValue())->toBeString();
    }
});
