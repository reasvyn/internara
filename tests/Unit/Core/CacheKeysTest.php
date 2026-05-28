<?php

declare(strict_types=1);

use App\Domain\Core\Support\CacheKeys;

describe('CacheKeys', function () {
    it('is final readonly class', function () {
        $reflection = new ReflectionClass(CacheKeys::class);

        expect($reflection->isFinal())->toBeTrue()
            ->and($reflection->isReadOnly())->toBeTrue();
    });

    it('defines setup installed key', function () {
        expect(CacheKeys::SETUP_INSTALLED)->toBe('setup.is_installed');
    });

    it('defines admin dashboard stats key', function () {
        expect(CacheKeys::ADMIN_DASHBOARD_STATS)->toBe('admin.dashboard.stats');
    });

    it('defines theme css variables key', function () {
        expect(CacheKeys::THEME_CSS_VARIABLES)->toBe('theme.css_variables');
    });

    it('defines notification unread key pattern', function () {
        expect(CacheKeys::NOTIFICATION_UNREAD)->toBe('notification.unread:');
    });

    it('defines auth login failures key pattern', function () {
        expect(CacheKeys::AUTH_LOGIN_FAILURES)->toBe('auth.login-failures:');
    });

    it('defines domain discovery keys', function () {
        expect(CacheKeys::DOMAIN_LIVEWIRE)->toBe('domain.discovered_livewire')
            ->and(CacheKeys::DOMAIN_POLICIES)->toBe('domain.discovered_policies')
            ->and(CacheKeys::DOMAIN_VIEWS)->toBe('domain.discovered_views');
    });

    it('follows dot-notation naming convention', function () {
        $reflection = new ReflectionClass(CacheKeys::class);
        $constants = $reflection->getConstants();

        foreach ($constants as $name => $value) {
            expect($value)->toMatch('/^[a-z0-9_.:{}\-]+$/');
        }
    });

    it('uses unique values across all keys', function () {
        $reflection = new ReflectionClass(CacheKeys::class);
        $values = array_values($reflection->getConstants());

        expect($values)->toHaveCount(count(array_unique($values)));
    });
});
