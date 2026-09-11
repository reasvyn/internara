<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Http\Middleware\AuthThrottleMiddleware;
use App\Modules\Auth\Domain\Permissions\Http\Middleware\CheckRoleMiddleware;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(LazilyRefreshDatabase::class);

function roleRequest(?User $user, bool $json = false): Request
{
    $request = Request::create('/admin/users', 'GET');
    if ($user !== null) {
        $request->setUserResolver(fn () => $user);
    }
    if ($json) {
        $request->headers->set('Accept', 'application/json');
    }

    return $request;
}

describe('2CF4Y: role gate and login throttle middleware', function (): void {
    test('2CF4Y-FR-MID-006: user holding the required role passes through', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = app(CheckRoleMiddleware::class)->handle(
            roleRequest($admin),
            fn () => response('allowed', 200),
            'admin',
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('allowed');
    });

    test('2CF4Y-FR-MID-006: user without the role gets a JSON 403 denial', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $response = app(CheckRoleMiddleware::class)->handle(
            roleRequest($student, true),
            fn () => response('allowed', 200),
            'admin',
        );

        expect($response->getStatusCode())->toBe(403)
            ->and($response->getData(true)['message'])->toContain('Security Access Denied');
    });

    test('2CF4Y-FR-MID-006: guest without JSON is sent back to login', function (): void {
        $response = app(CheckRoleMiddleware::class)->handle(
            roleRequest(null),
            fn () => response('allowed', 200),
            'admin',
        );

        expect($response->isRedirect(route('login')))->toBeTrue();
    });

    test('2CF4Y-FR-MID-006: pipe-separated roles admit any listed role', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $response = app(CheckRoleMiddleware::class)->handle(
            roleRequest($teacher),
            fn () => response('allowed', 200),
            'admin|teacher',
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('allowed');
    });

    test('2CF4Y-FR-MID-006: wrong role on web aborts with translated 403', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        try {
            app(CheckRoleMiddleware::class)->handle(
                roleRequest($student),
                fn () => response('allowed', 200),
                'admin',
            );

            $this->fail('Expected a 403 abort for the wrong role.');
        } catch (HttpException $e) {
            expect($e->getStatusCode())->toBe(403);
        }
    });

    test('2CF4Y-FR-MID-005: login attempts past the limit are throttled with 429', function (): void {
        config()->set('auth.throttle.login_max_attempts', 2);
        config()->set('auth.throttle.login_decay_seconds', 60);

        $identifier = 'throttle-probe-'.uniqid().'@example.test';
        $attempt = function (bool $json = false) use ($identifier) {
            $request = Request::create('/login', 'POST', ['identifier' => $identifier]);
            if ($json) {
                $request->headers->set('Accept', 'application/json');
            }

            return $request;
        };

        $middleware = app(AuthThrottleMiddleware::class);
        $first = $middleware->handle($attempt(), fn () => response('ok', 200));
        $second = $middleware->handle($attempt(), fn () => response('ok', 200));
        $third = $middleware->handle($attempt(true), fn () => response('ok', 200));

        expect($first->getStatusCode())->toBe(200)
            ->and($second->getStatusCode())->toBe(200)
            ->and($third->getStatusCode())->toBe(429)
            ->and((string) $third->getData()->message)->not->toBeEmpty();
    });

    test('2CF4Y-FR-MID-005: GET requests never consume the throttle budget', function (): void {
        config()->set('auth.throttle.login_max_attempts', 1);
        config()->set('auth.throttle.login_decay_seconds', 60);

        $middleware = app(AuthThrottleMiddleware::class);
        $identifier = 'get-probe-'.uniqid().'@example.test';

        foreach (range(1, 3) as $i) {
            $response = $middleware->handle(
                Request::create('/login', 'GET', ['identifier' => $identifier]),
                fn () => response('ok', 200),
            );

            expect($response->getStatusCode())->toBe(200);
        }
    });

    test('2CF4Y-FR-MID-011: default config enforces five logins per sixty seconds', function (): void {
        expect((int) config('auth.throttle.login_max_attempts'))->toBe(5)
            ->and((int) config('auth.throttle.login_decay_seconds'))->toBe(60);

        $middleware = app(AuthThrottleMiddleware::class);
        $identifier = 'canonical-probe-'.uniqid().'@example.test';
        $attempt = function (bool $json = false) use ($identifier) {
            $request = Request::create('/login', 'POST', ['identifier' => $identifier]);
            if ($json) {
                $request->headers->set('Accept', 'application/json');
            }

            return $request;
        };

        foreach (range(1, 5) as $i) {
            $response = $middleware->handle($attempt(), fn () => response('ok', 200));
            expect($response->getStatusCode())->toBe(200);
        }

        $blocked = $middleware->handle($attempt(true), fn () => response('ok', 200));
        expect($blocked->getStatusCode())->toBe(429);
    });
});
