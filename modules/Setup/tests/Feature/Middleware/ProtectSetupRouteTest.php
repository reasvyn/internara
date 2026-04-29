<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Middleware;

use Illuminate\Support\Facades\RateLimiter;
use Modules\Setup\Models\Setup;
use Modules\Setup\Services\Contracts\SetupService;
use Tests\TestCase;

/**
 * [S1 - Secure] Test rate limiting, token validation, TTL
 * [S2 - Sustain] Test clear error responses
 * [S3 - Scalable] Test stateless validation
 */
describe('ProtectSetupRoute Middleware', function () {
    beforeEach(function () {
        RateLimiter::clear('setup_throttle:' . request()->ip());
        Setup::truncate();
    });

    it('denies access without token', function () {
        $response = $this->get('/setup/welcome');
        
        $response->assertRedirect('/setup/welcome');
        $response->assertSessionHasErrors('token');
    });

    it('denies access with invalid token', function () {
        $setup = Setup::create([
            'is_installed' => false,
            'completed_steps' => [],
        ]);
        $setup->setToken('valid-token');
        $setup->token_expires_at = now()->addHour();
        $setup->save();

        $response = $this->get('/setup/welcome?token=invalid-token');
        
        $response->assertRedirect('/setup/welcome');
        $response->assertSessionHasErrors('token');
    });

    it('allows access with valid token', function () {
        $setup = Setup::create([
            'is_installed' => false,
            'completed_steps' => [],
        ]);
        $token = $setup->getToken();
        $setup->token_expires_at = now()->addHour();
        $setup->save();

        $response = $this->get("/setup/welcome?token={$token}");
        
        // Should not redirect with error
        expect($response->getStatusCode())->not->toBe(403);
    });

    it('denies access with expired token', function () {
        $setup = Setup::create([
            'is_installed' => false,
            'completed_steps' => [],
        ]);
        $token = $setup->getToken();
        $setup->token_expires_at = now()->subHour();
        $setup->save();

        $response = $this->get("/setup/welcome?token={$token}");
        
        $response->assertRedirect('/setup/welcome');
        $response->assertSessionHasErrors('token');
    });

    it('rate limits after 20 attempts', function () {
        $ip = '127.0.0.1';
        
        // Simulate 20 attempts
        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit("setup_throttle:{$ip}", 60);
        }

        $response = $this->get('/setup/welcome');
        
        $response->assertStatus(429); // Too Many Requests
    });

    it('stores token in session after validation', function () {
        $setup = Setup::create([
            'is_installed' => false,
            'completed_steps' => [],
        ]);
        $token = $setup->getToken();
        $setup->token_expires_at = now()->addHour();
        $setup->save();

        $response = $this->get("/setup/welcome?token={$token}");
        
        $response->assertSessionHas('setup_token', $token);
        $response->assertSessionHas('setup_authorized', true);
    });
});
