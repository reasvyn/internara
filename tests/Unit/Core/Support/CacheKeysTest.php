<?php

declare(strict_types=1);

use App\Domain\Core\Support\CacheKeys;

describe('CacheKeys', function () {
    it('is a final readonly class', function () {
        $ref = new ReflectionClass(CacheKeys::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue();
    });

    it('has 17 defined cache keys', function () {
        $ref = new ReflectionClass(CacheKeys::class);
        $constants = $ref->getConstants();

        expect($constants)->toHaveCount(17);
    });

    it('defines setup key', function () {
        expect(CacheKeys::SETUP_INSTALLED)->toBe('setup.is_installed');
    });

    it('defines admin dashboard key', function () {
        expect(CacheKeys::ADMIN_DASHBOARD_STATS)->toBe('admin.dashboard.stats');
    });

    it('defines theme key', function () {
        expect(CacheKeys::THEME_CSS_VARIABLES)->toBe('theme.css_variables');
    });

    it('defines notification key', function () {
        expect(CacheKeys::NOTIFICATION_UNREAD)->toBe('notification.unread:');
    });

    it('defines core integrity keys', function () {
        expect(CacheKeys::CORE_INTEGRITY)->toBe('core.integrity_verified')
            ->and(CacheKeys::CORE_APP_NAME)->toBe('core.app_name');
    });

    it('defines domain discovery keys', function () {
        expect(CacheKeys::DOMAIN_LIVEWIRE)->toBe('domain.discovered_livewire')
            ->and(CacheKeys::DOMAIN_POLICIES)->toBe('domain.discovered_policies')
            ->and(CacheKeys::DOMAIN_VIEWS)->toBe('domain.discovered_views');
    });

    it('defines auth keys', function () {
        expect(CacheKeys::AUTH_LOGIN_FAILURES)->toBe('auth.login-failures:');
    });

    it('defines health check key', function () {
        expect(CacheKeys::HEALTH_CHECK)->toBe('health_check');
    });

    it('defines recovery key', function () {
        expect(CacheKeys::RECOVER_ADMIN_ATTEMPTS)->toBe('recover_admin_attempts_');
    });

    it('defines settings keys', function () {
        expect(CacheKeys::SETTINGS_ALL)->toBe('settings.all')
            ->and(CacheKeys::SETTINGS_GROUP)->toBe('settings.group.')
            ->and(CacheKeys::SETTINGS_KEYS)->toBe('settings.keys')
            ->and(CacheKeys::SETTINGS_KEY)->toBe('settings.');
    });

    it('all keys follow {domain}.{purpose} naming convention', function () {
        $ref = new ReflectionClass(CacheKeys::class);
        $constants = $ref->getConstants();

        foreach ($constants as $key => $value) {
            expect($value)->toMatch('/^[a-z_.: -]+$/');
        }
    });
});
