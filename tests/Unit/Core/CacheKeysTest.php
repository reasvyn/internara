<?php

declare(strict_types=1);

use App\Domain\Core\Support\CacheKeys;

describe('CacheKeys', function () {
    it('defines all expected cache keys', function () {
        expect(CacheKeys::SETUP_INSTALLED)->toBe('setup.is_installed')
            ->and(CacheKeys::ADMIN_DASHBOARD_STATS)->toBe('admin.dashboard.stats')
            ->and(CacheKeys::THEME_CSS_VARIABLES)->toBe('theme.css_variables')
            ->and(CacheKeys::NOTIFICATION_UNREAD)->toBe('notification.unread:')
            ->and(CacheKeys::AUTH_LOGIN_FAILURES)->toBe('auth.login-failures:')
            ->and(CacheKeys::DOMAIN_LIVEWIRE)->toBe('domain.discovered_livewire')
            ->and(CacheKeys::DOMAIN_POLICIES)->toBe('domain.discovered_policies')
            ->and(CacheKeys::DOMAIN_VIEWS)->toBe('domain.discovered_views')
            ->and(CacheKeys::APPINFO_METADATA)->toBe('appinfo.metadata')
            ->and(CacheKeys::HEALTH_CHECK)->toBe('health_check')
            ->and(CacheKeys::SETTINGS_ALL)->toBe('settings.all')
            ->and(CacheKeys::SETTINGS_GROUP)->toBe('settings.group.')
            ->and(CacheKeys::SETTINGS_KEYS)->toBe('settings.keys')
            ->and(CacheKeys::SETTINGS_KEY)->toBe('settings.')
            ->and(CacheKeys::RECOVER_ADMIN_ATTEMPTS)->toBe('recover_admin_attempts_');
    });

    it('has unique values across all keys', function () {
        $ref = new ReflectionClass(CacheKeys::class);
        $values = array_values($ref->getConstants());

        expect($values)->toHaveCount(count(array_unique($values)));
    });
});
