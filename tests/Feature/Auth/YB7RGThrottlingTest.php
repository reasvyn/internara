<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Actions\LoginAction;
use App\Modules\Auth\Domain\Login\Data\LoginData;
use App\Modules\Auth\Domain\Login\Events\LoginFailed;
use App\Modules\Auth\Domain\Login\Http\Middleware\AuthThrottleMiddleware;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: throttling and lockout', function (): void {
    test('YB7RG-FR-AUTH-009 + YB7RG-UC-AUTH-005: sixth login post inside a minute answers 429', function (): void {
        $middleware = app(AuthThrottleMiddleware::class);
        $identifier = 'flood-probe-'.uniqid().'@example.test';

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

        expect($blocked->getStatusCode())->toBe(429)
            ->and((string) $blocked->getData()->message)->toMatch('/\d+/');
    });

    test('YB7RG-FR-AUTH-011 + YB7RG-NFR-AUTH-004 + YB7RG-UC-AUTH-002: backoff durations double 10s, 20s, 40s', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();
        $action = app(LoginAction::class);
        $attemptsKey = config('cache-keys.auth_login_attempts').hash('crc32b', $user->email);
        $lockoutKey = config('cache-keys.auth_login_lockout').hash('crc32b', $user->email);

        $fail = function () use ($action, $user): void {
            try {
                $action->execute(new LoginData(identifier: $user->email, password: 'wrong-password'));
            } catch (RejectedException) {
            }
        };

        $durationOf = fn (): int => Carbon::parse(Cache::get($lockoutKey))->timestamp - now()->timestamp;

        $t0 = now();
        Carbon::setTestNow($t0);

        try {
            for ($i = 0; $i < 10; $i++) {
                $fail();
            }

            expect(Cache::get($attemptsKey))->toBe(10);

            $d10 = $durationOf();

            expect($d10)->toBeGreaterThanOrEqual(9)->toBeLessThanOrEqual(11);

            Carbon::setTestNow($t0->copy()->addSeconds(11));
            $fail();

            expect(Cache::get($attemptsKey))->toBe(11);

            $d11 = $durationOf();

            expect($d11)->toBeGreaterThanOrEqual(19)->toBeLessThanOrEqual(21);

            Carbon::setTestNow($t0->copy()->addSeconds(32));
            $fail();

            expect(Cache::get($attemptsKey))->toBe(12);

            $d12 = $durationOf();

            expect($d12)->toBeGreaterThanOrEqual(39)->toBeLessThanOrEqual(41)
                ->and($d11)->toBeGreaterThan($d10)
                ->and($d12)->toBeGreaterThan($d11);
        } finally {
            Carbon::setTestNow();
        }
    });

    test('YB7RG-FR-AUTH-012 + YB7RG-NFR-AUTH-006: attempts counter lives under a crc32b key with 24h TTL', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();
        $hash = hash('crc32b', $user->email);

        expect($hash)->toMatch('/^[0-9a-f]{8}$/');

        $attemptsKey = config('cache-keys.auth_login_attempts').$hash;
        $lockoutKey = config('cache-keys.auth_login_lockout').$hash;

        expect($attemptsKey)->not->toContain($user->email)
            ->and($lockoutKey)->not->toContain($user->email);

        $action = app(LoginAction::class);
        $t0 = now();
        Carbon::setTestNow($t0);

        try {
            for ($i = 0; $i < 3; $i++) {
                try {
                    $action->execute(new LoginData(identifier: $user->email, password: 'wrong-password'));
                } catch (RejectedException) {
                }
            }

            expect(Cache::get($attemptsKey))->toBe(3)
                ->and(Cache::get($lockoutKey))->toBeNull();

            Carbon::setTestNow($t0->copy()->addHours(23));

            expect(Cache::get($attemptsKey))->toBe(3);

            Carbon::setTestNow($t0->copy()->addHours(25));

            expect(Cache::get($attemptsKey))->toBeNull();
        } finally {
            Carbon::setTestNow();
        }
    });

    test('YB7RG-FR-AUTH-013: lockout flag TTL equals the backoff duration, then evaporates', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();
        $action = app(LoginAction::class);
        $attemptsKey = config('cache-keys.auth_login_attempts').hash('crc32b', $user->email);
        $lockoutKey = config('cache-keys.auth_login_lockout').hash('crc32b', $user->email);

        $t0 = now();
        Carbon::setTestNow($t0);

        try {
            for ($i = 0; $i < 10; $i++) {
                try {
                    $action->execute(new LoginData(identifier: $user->email, password: 'wrong-password'));
                } catch (RejectedException) {
                }
            }

            $remaining = Carbon::parse(Cache::get($lockoutKey))->timestamp - now()->timestamp;

            expect($remaining)->toBeGreaterThanOrEqual(9)->toBeLessThanOrEqual(10);

            Carbon::setTestNow($t0->copy()->addSeconds(11));

            expect(Cache::get($lockoutKey))->toBeNull()
                ->and(Cache::get($attemptsKey))->toBe(10);
        } finally {
            Carbon::setTestNow();
        }
    });

    test('YB7RG-FR-AUTH-014 + YB7RG-UC-AUTH-004: waiting out the backoff then succeeding clears both counters', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();
        $action = app(LoginAction::class);
        $attemptsKey = config('cache-keys.auth_login_attempts').hash('crc32b', $user->email);
        $lockoutKey = config('cache-keys.auth_login_lockout').hash('crc32b', $user->email);

        $t0 = now();
        Carbon::setTestNow($t0);

        try {
            for ($i = 0; $i < 10; $i++) {
                try {
                    $action->execute(new LoginData(identifier: $user->email, password: 'wrong-password'));
                } catch (RejectedException) {
                }
            }

            expect(Cache::get($lockoutKey))->not->toBeNull();

            Carbon::setTestNow($t0->copy()->addSeconds(11));

            $result = $action->execute(new LoginData(identifier: $user->email, password: 'secret-123'));

            expect($result->id)->toBe($user->id)
                ->and(auth()->id())->toBe($user->id)
                ->and(Cache::get($attemptsKey))->toBeNull()
                ->and(Cache::get($lockoutKey))->toBeNull();
        } finally {
            Carbon::setTestNow();
        }
    });

    test('YB7RG-FR-AUTH-015 + YB7RG-NFR-AUTH-005 + YB7RG-NFR-AUTH-007: unknown identifiers consume budget; lockouts name the wait', function (): void {
        Event::fake([LoginFailed::class]);

        $ghost = 'ghost-'.uniqid().'@example.test';
        $hash = hash('crc32b', $ghost);
        $attemptsKey = config('cache-keys.auth_login_attempts').$hash;
        $lockoutKey = config('cache-keys.auth_login_lockout').$hash;
        $action = app(LoginAction::class);

        try {
            $action->execute(new LoginData(identifier: $ghost, password: 'whatever-123'));
            $this->fail('Expected RejectedException for an unknown identifier.');
        } catch (RejectedException) {
        }

        Event::assertDispatched(LoginFailed::class, fn (LoginFailed $e) => $e->identifier === $ghost && $e->reason === 'user_not_found');

        for ($i = 0; $i < 9; $i++) {
            try {
                $action->execute(new LoginData(identifier: $ghost, password: 'whatever-123'));
            } catch (RejectedException) {
            }
        }

        expect(Cache::get($attemptsKey))->toBe(10)
            ->and(Cache::get($lockoutKey))->not->toBeNull();

        try {
            $action->execute(new LoginData(identifier: $ghost, password: 'whatever-123'));
            $this->fail('Expected RejectedException for a locked-out unknown identifier.');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('auth.failed'));
        }

        $user = User::factory()->withPassword('secret-123')->create();
        $t0 = now();
        Carbon::setTestNow($t0);

        try {
            for ($i = 0; $i < 10; $i++) {
                try {
                    $action->execute(new LoginData(identifier: $user->email, password: 'wrong-password'));
                } catch (RejectedException) {
                }
            }

            try {
                $action->execute(new LoginData(identifier: $user->email, password: 'secret-123'));
                $this->fail('Expected a lockout RejectedException for the known user.');
            } catch (RejectedException $e) {
                expect($e->getMessage())->toBe(__('auth.throttle', ['seconds' => 10]))
                    ->and($e->getMessage())->not->toBe(__('auth.failed'));
            }
        } finally {
            Carbon::setTestNow();
        }
    });
});
