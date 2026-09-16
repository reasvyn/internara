<?php

declare(strict_types=1);

use App\Modules\Core\Support\ModuleManager;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

describe('2CF4Y middleware contracts', function (): void {
    test('2CF4Y-FR-MID-001/003/004/012/013 and 2CF4Y-NFR-SEC-004: web bootstrap keeps the ordered session pipeline', function (): void {
        $source = file_get_contents(base_path('bootstrap/app.php'));

        expect($source)->toContain('LogContextMiddleware::class')
            ->and($source)->toContain('SecurityHeadersMiddleware::class')
            ->and($source)->toContain('preventRequestForgery')
            ->and($source)->toContain('CheckRoleMiddleware::class')
            ->and($source)->toContain('SetLocaleMiddleware::class')
            ->and($source)->not->toContain('Sanctum');

        expect(strpos($source, 'SecurityHeadersMiddleware::class'))
            ->toBeLessThan(strpos($source, 'LogContextMiddleware::class'));
    });

    test('2CF4Y-FR-MID-010/011 and 2CF4Y-NFR-MID-002: named limiters use canonical cache-backed budgets', function (): void {
        $source = file_get_contents(base_path('app/Providers/AppServiceProvider.php'));
        expect($source)->toContain("RateLimiter::for('admin'")
            ->and($source)->toContain("'global',")
            ->and(config('auth.throttle.login_max_attempts'))->toBe(5)
            ->and(config('auth.throttle.login_decay_seconds'))->toBe(60)
            ->and(config('cache.default'))->not->toBe('database')
            ->and(RateLimiter::limiter('admin'))->not->toBeNull()
            ->and(RateLimiter::limiter('global'))->not->toBeNull();
    });

    test('2CF4Y-NFR-MID-001/003: development and logging resilience remain observable in implementation', function (): void {
        $source = file_get_contents(base_path('app/Modules/Core/Http/Middleware/LogContextMiddleware.php'));
        $headers = file_get_contents(base_path('app/Modules/Core/Http/Middleware/SecurityHeadersMiddleware.php'));

        expect($source)->toContain('Log::withContext')
            ->and($source)->toContain('microtime(true)')
            ->and($headers)->toContain('Content-Security-Policy')
            ->and(config('security-headers.headers'))->toHaveKey('Permissions-Policy');
    });
});

describe('I1BCV discovery contracts', function (): void {
    test('I1BCV-NFR-MOD-001/003/004/005/006/007/008 and I1BCV-DD-MOD-001/002/003: registry is bounded and deterministic', function (): void {
        $modules = ModuleManager::names();
        $registry = ModuleManager::registry();

        expect($modules)->toHaveCount(19)
            ->and($modules)->toBe(array_values(array_unique($modules)))
            ->and($modules)->toBe(collect($modules)->sort()->values()->all())
            ->and(array_keys($registry))->toBe($modules)
            ->and(ModuleManager::testDirs())->toBe(['Providers', 'Stubs', 'Support'])
            ->and($modules)->toContain('Academic', 'Journal', 'Partner', 'Report', 'Setting');

        foreach ($modules as $module) {
            expect($module)->toMatch('/^[A-Z][A-Za-z0-9]+$/')
                ->and(ModuleManager::domains($module))->toBeArray();
        }
    });

    test('I1BCV-NFR-MOD-004/005/006/007/008 and I1BCV-DD-MOD-004/005: discovery reads the configured filesystem boundaries', function (): void {
        $source = file_get_contents(base_path('app/Modules/Core/Services/ModuleService.php'));

        expect($source)->toContain('ModuleManager::basePath()')
            ->and($source)->toContain('ModuleManager::names()')
            ->and($source)->toContain('CACHE_TTL_SECONDS = 86400')
            ->and(ModuleManager::basePath())->toBe(app_path('Modules'))
            ->and(ModuleManager::viewsPath())->toBe(resource_path('views'))
            ->and(ModuleManager::routesPath())->toBe(base_path('routes/web'));
    });
});
