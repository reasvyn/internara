<?php

declare(strict_types=1);

use App\Modules\Core\Http\Middleware\LogContextMiddleware;
use App\Modules\Core\Http\Middleware\SecurityHeadersMiddleware;
use App\Modules\User\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

describe('2CF4Y: middleware pipeline context and resilience', function (): void {
    test('2CF4Y-FR-MID-002: LogContextMiddleware attaches identity, timing and request envelope', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $request = Request::create('http://localhost/admin/users', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = app(LogContextMiddleware::class)->handle(
            $request,
            fn () => response('ok', 200),
        );

        Log::info('pipeline probe');
        $record = $captured->firstWhere('message', 'pipeline probe');

        expect($response->getStatusCode())->toBe(200)
            ->and($record->context['request_id'])->not->toBeEmpty()
            ->and($record->context['user_id'])->toBe($user->id)
            ->and($record->context['user_role'])->toBe('teacher')
            ->and($record->context['status'])->toBe(200)
            ->and($record->context['duration_ms'])->toBeNumeric();
    });

    test('2CF4Y-NFR-MID-003: request succeeds when the log sink is unwritable', function (): void {
        config()->set('logging.default', 'single');
        config()->set('logging.channels.single.path', '/proc/internara-unwritable-xyz/laravel.log');

        $request = Request::create('http://localhost/admin/users', 'GET');

        $response = app(LogContextMiddleware::class)->handle(
            $request,
            fn () => response('ok', 200),
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('ok');
    });

    test('2CF4Y-UC-MID-001, 2CF4Y-DD-MID-001: core middleware pipeline registers LogContext and SecurityHeaders at documented positions', function (): void {
        $source = file_get_contents(base_path('bootstrap/app.php'));

        expect($source)->toContain('LogContextMiddleware::class')
            ->and($source)->toContain('SecurityHeadersMiddleware::class')
            ->and(class_exists(LogContextMiddleware::class))->toBeTrue()
            ->and(class_exists(SecurityHeadersMiddleware::class))->toBeTrue();
    });

    test('2CF4Y-UC-MID-002: module-specific middleware is scoped to route groups via alias or class', function (): void {
        $source = file_get_contents(base_path('bootstrap/app.php'));

        expect($source)->toContain('ProtectSetupRouteMiddleware::class')
            ->and($source)->toContain("'setup.protected'")
            ->and($source)->toContain("'role'")
            ->and($source)->toContain("'auth.throttle'");
    });

    test('2CF4Y-UC-MID-003, 2CF4Y-DD-MID-003: rate limiting is registered through named limiters over inline configuration', function (): void {
        $adminLimiter = RateLimiter::limiter('admin');
        $globalLimiter = RateLimiter::limiter('global');

        expect($adminLimiter)->not->toBeNull()
            ->and($globalLimiter)->not->toBeNull();

        $user = User::factory()->create();
        $adminLimit = $adminLimiter(Request::create('/admin', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']));
        $globalLimit = $globalLimiter(Request::create('/home', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']));

        expect($adminLimit)->toBeInstanceOf(Limit::class)
            ->and($globalLimit)->toBeInstanceOf(Limit::class)
            ->and($adminLimit->maxAttempts)->toBe(60)
            ->and($globalLimit->maxAttempts)->toBe(30);
    });

    test('2CF4Y-DD-MID-002: SecurityHeadersMiddleware injects security headers before route handling', function (): void {
        $request = Request::create('http://localhost/', 'GET');

        $response = app(SecurityHeadersMiddleware::class)->handle(
            $request,
            fn () => response('content', 200),
        );

        expect($response->headers->has('X-Frame-Options'))->toBeTrue()
            ->and($response->headers->has('X-Content-Type-Options'))->toBeTrue()
            ->and($response->headers->has('Referrer-Policy'))->toBeTrue();
    });

    test('2CF4Y-FR-GLB-005: canonical per-endpoint auth rate limit budgets are documented and configured', function (): void {
        expect(config('auth.throttle.login_max_attempts', 5))->toBe(5)
            ->and(config('auth.throttle.login_decay_seconds', 60))->toBe(60);

        $spec = file_get_contents(base_path('docs/specs/2CF4Y-middleware-pipeline.md'));
        expect($spec)->toContain('FR-GLB-005')
            ->and($spec)->toContain('login 5/60s')
            ->and($spec)->toContain('forgot 3/3600s');
    });
});
