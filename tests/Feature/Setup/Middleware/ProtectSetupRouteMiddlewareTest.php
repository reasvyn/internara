<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Middleware;

use App\Domain\Setup\Models\Setup;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Setup::query()->delete();
    Session::flush();
    RateLimiter::clear('setup:127.0.0.1');
});

describe('ProtectSetupRouteMiddleware', function () {
    it('allows access when session is authorized', function () {
        Session::put('setup.authorized', true);

        $this->get(route('setup'))
            ->assertStatus(200);
    });

    it('shows enter-code view when not authorized', function () {
        $this->get(route('setup'))
            ->assertViewIs('setup.enter-code');
    });

    it('validates token from query parameter and authorizes session', function () {
        $plaintext = 'valid-query-token';
        $setup = Setup::factory()->create([
            'setup_token' => Crypt::encryptString($plaintext),
            'token_expires_at' => now()->addHour(),
        ]);

        $this->get(route('setup', ['setup_token' => $plaintext]))
            ->assertStatus(200);

        expect(Session::get('setup.authorized'))->toBeTrue();
    });

    it('returns 404 when installed and no completed session', function () {
        Setup::factory()->installed()->create();

        $this->get(route('setup'))
            ->assertStatus(404);
    });

    it('allows access within finalization window after completion', function () {
        $setup = Setup::factory()->installed()->create([
            'updated_at' => now(),
        ]);
        Session::put('setup.completed', true);

        $this->get(route('setup'))
            ->assertStatus(200);
    });

    it('returns 404 if outside finalization window even with completed session', function () {
        $setup = Setup::factory()->installed()->create([
            'updated_at' => now()->subMinutes(5),
        ]);
        Session::put('setup.completed', true);

        $this->get(route('setup'))
            ->assertStatus(404);
    });

    it('rate limits invalid token attempts', function () {
        config(['setup.security.rate_limit_attempts' => 2]);
        config(['setup.security.rate_limit_decay_seconds' => 60]);

        $this->get(route('setup', ['setup_token' => 'wrong1']))->assertStatus(403);
        $this->get(route('setup', ['setup_token' => 'wrong2']))->assertStatus(403);
        $this->get(route('setup', ['setup_token' => 'wrong3']))->assertStatus(429);
    });

    it('returns 404 when installed and no completion session', function () {
        Setup::factory()->installed()->create();

        $this->get(route('setup'))
            ->assertStatus(404);
    });
});
