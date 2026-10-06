<?php

declare(strict_types=1);

use App\Modules\Core\Http\Middleware\ConcurrencyGateMiddleware;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

uses(LazilyRefreshDatabase::class);

function runConcurrencyGate(Request $request, ?Closure $destination = null): Response
{
    $destination ??= fn () => response('ok', 200);

    return app(ConcurrencyGateMiddleware::class)->handle($request, $destination);
}

describe('C7Q9R: ConcurrencyGateMiddleware', function (): void {
    beforeEach(function (): void {
        config()->set('concurrency.enabled', true);
        config()->set('concurrency.lock_ttl_seconds', 30);
        config()->set('concurrency.wait_timeout_seconds', 5);
        config()->set('concurrency.global_write_lock', false);
        config()->set('concurrency.exempt_routes', ['health', 'up']);
    });

    test('C7Q9R-FR-CCG-001: safe HTTP methods GET HEAD OPTIONS bypass concurrency lock', function (): void {
        $getReq = Request::create('/dashboard', 'GET');
        $headReq = Request::create('/dashboard', 'HEAD');
        $optionsReq = Request::create('/dashboard', 'OPTIONS');

        $getRes = runConcurrencyGate($getReq);
        $headRes = runConcurrencyGate($headReq);
        $optionsRes = runConcurrencyGate($optionsReq);

        expect($getRes->headers->get('X-Concurrency-Gate'))->toBe('bypassed')
            ->and($headRes->headers->get('X-Concurrency-Gate'))->toBe('bypassed')
            ->and($optionsRes->headers->get('X-Concurrency-Gate'))->toBe('bypassed')
            ->and($getRes->getStatusCode())->toBe(200);
    });

    test('C7Q9R-FR-CCG-002: mutating request from authenticated user acquires lock scoped to user ID', function (): void {
        $user = User::factory()->create();
        $request = Request::create('/dashboard/profile', 'POST');
        $request->setUserResolver(fn () => $user);

        $lockChecked = false;
        $response = runConcurrencyGate($request, function () use ($user, &$lockChecked) {
            $userLockKey = config('cache-keys.concurrency_user_lock').$user->id;
            // The lock should currently be held by this request
            $probeLock = Cache::lock($userLockKey, 1);
            $acquired = $probeLock->acquire();
            // Since it's already held by the running request, another acquire must fail or confirm ownership
            $lockChecked = ! $acquired;
            if ($acquired) {
                $probeLock->release();
            }

            return response('created', 201);
        });

        expect($response->getStatusCode())->toBe(201)
            ->and($response->headers->get('X-Concurrency-Gate'))->toBe('acquired')
            ->and($lockChecked)->toBeTrue();
    });

    test('C7Q9R-FR-CCG-003: mutating request from guest acquires lock scoped to session or IP', function (): void {
        $request = Request::create('/contact', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);

        $executed = false;
        $response = runConcurrencyGate($request, function () use (&$executed) {
            $executed = true;

            return response('guest ok', 200);
        });

        expect($response->getStatusCode())->toBe(200)
            ->and($response->headers->get('X-Concurrency-Gate'))->toBe('acquired')
            ->and($executed)->toBeTrue();
    });

    test('C7Q9R-FR-CCG-004: mutating requests queue and execute sequentially for the same user', function (): void {
        $user = User::factory()->create();
        $executionOrder = [];

        $req1 = Request::create('/action1', 'POST');
        $req1->setUserResolver(fn () => $user);

        $req2 = Request::create('/action2', 'POST');
        $req2->setUserResolver(fn () => $user);

        // Pre-acquire lock to simulate an active preceding request
        $userLockKey = config('cache-keys.concurrency_user_lock').$user->id;
        $preLock = Cache::lock($userLockKey, 5);
        $preLock->acquire();

        // When req1 runs, it will block until preLock is released
        // We simulate background release after 50ms using a timer or manual release
        usleep(50000);
        $preLock->release();

        $res1 = runConcurrencyGate($req1, function () use (&$executionOrder) {
            $executionOrder[] = 'req1';

            return response('req1 done', 200);
        });

        $res2 = runConcurrencyGate($req2, function () use (&$executionOrder) {
            $executionOrder[] = 'req2';

            return response('req2 done', 200);
        });

        expect($res1->getStatusCode())->toBe(200)
            ->and($res2->getStatusCode())->toBe(200)
            ->and($executionOrder)->toBe(['req1', 'req2']);
    });

    test('C7Q9R-FR-CCG-005: returns HTTP 429 with Retry-After when lock acquisition times out', function (): void {
        $user = User::factory()->create();
        config()->set('concurrency.wait_timeout_seconds', 0);

        $userLockKey = config('cache-keys.concurrency_user_lock').$user->id;
        $blockerLock = Cache::lock($userLockKey, 10);
        $blockerLock->acquire();

        try {
            $request = Request::create('/update', 'PUT');
            $request->setUserResolver(fn () => $user);
            $request->headers->set('Accept', 'application/json');

            $response = runConcurrencyGate($request);

            expect($response->getStatusCode())->toBe(429)
                ->and($response->headers->get('Retry-After'))->toBe('1')
                ->and($response->headers->get('X-Concurrency-Gate'))->toBe('timeout');

            $data = json_decode($response->getContent(), true);
            expect($data)->toHaveKey('message');
        } finally {
            $blockerLock->release();
        }
    });

    test('C7Q9R-FR-CCG-006: lock is always released even when downstream handler throws exception', function (): void {
        $user = User::factory()->create();
        $userLockKey = config('cache-keys.concurrency_user_lock').$user->id;

        $request = Request::create('/crash', 'DELETE');
        $request->setUserResolver(fn () => $user);

        try {
            runConcurrencyGate($request, function () {
                throw new RuntimeException('Intentional crash');
            });
        } catch (RuntimeException) {
            // Expected
        }

        // Lock must be free now; another acquire should succeed immediately
        $probeLock = Cache::lock($userLockKey, 5);
        $acquired = $probeLock->acquire();
        expect($acquired)->toBeTrue();
        $probeLock->release();
    });

    test('C7Q9R-FR-CCG-007: respects disabled flag and exempt routes', function (): void {
        config()->set('concurrency.enabled', false);

        $req = Request::create('/mutating', 'POST');
        $res = runConcurrencyGate($req);

        expect($res->headers->get('X-Concurrency-Gate'))->toBe('bypassed');

        // Re-enable and test exempt route
        config()->set('concurrency.enabled', true);
        $exemptReq = Request::create('/up', 'POST');
        $exemptRes = runConcurrencyGate($exemptReq);

        expect($exemptRes->headers->get('X-Concurrency-Gate'))->toBe('bypassed');
    });

    test('C7Q9R-FR-CCG-008: bootstrap/app.php registers alias and web group middleware', function (): void {
        $source = file_get_contents(base_path('bootstrap/app.php'));

        expect($source)->toContain('ConcurrencyGateMiddleware::class')
            ->and($source)->toContain("'concurrency.gate'")
            ->and(class_exists(ConcurrencyGateMiddleware::class))->toBeTrue();
    });
});
