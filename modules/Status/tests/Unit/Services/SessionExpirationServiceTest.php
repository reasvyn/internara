<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Status\Services\SessionExpirationService;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * SessionExpirationServiceTest
 *
 * Tests role-based session timeout enforcement:
 * - Session initialization
 * - Expiration detection
 * - Remaining time calculation
 * - Role-based timeout periods
 */
class SessionExpirationServiceTest extends TestCase
{
    use RefreshDatabase;

    private SessionExpirationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SessionExpirationService::class);
        Cache::flush();
    }

    /** @test */
    public function records_session_start()
    {
        $user = User::factory()->create();

        $this->service->recordSessionStart($user, '127.0.0.1', 'Test User Agent');

        $cached = Cache::get("session_user_{$user->id}");
        $this->assertNotNull($cached);
        $this->assertEquals($user->id, $cached['user_id']);
    }

    /** @test */
    public function super_admin_session_expires_after_12_hours()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->service->recordSessionStart($user, '127.0.0.1', 'Test');

        // Not expired yet
        $this->assertFalse($this->service->isExpired($user));

        // Simulate time passage
        Cache::put(
            "session_user_{$user->id}",
            [
                'user_id' => $user->id,
                'started_at' => now()->subHours(13)->timestamp,
                'last_activity' => now()->subHours(13)->timestamp,
            ],
            minutes: 60,
        );

        // Now expired
        $this->assertTrue($this->service->isExpired($user));
    }

    /** @test */
    public function standard_user_session_expires_after_24_hours()
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->service->recordSessionStart($user, '127.0.0.1', 'Test');

        // Simulate 23 hours
        Cache::put(
            "session_user_{$user->id}",
            [
                'user_id' => $user->id,
                'started_at' => now()->subHours(23)->timestamp,
                'last_activity' => now()->subHours(23)->timestamp,
            ],
            minutes: 60,
        );

        // Not expired yet
        $this->assertFalse($this->service->isExpired($user));

        // Simulate 25 hours
        Cache::put(
            "session_user_{$user->id}",
            [
                'user_id' => $user->id,
                'started_at' => now()->subHours(25)->timestamp,
                'last_activity' => now()->subHours(25)->timestamp,
            ],
            minutes: 60,
        );

        // Now expired
        $this->assertTrue($this->service->isExpired($user));
    }

    /** @test */
    public function calculates_remaining_minutes_correctly()
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->service->recordSessionStart($user, '127.0.0.1', 'Test');

        // Simulate 12 hours elapsed (should have 12 hours remaining)
        Cache::put(
            "session_user_{$user->id}",
            [
                'user_id' => $user->id,
                'started_at' => now()->subHours(12)->timestamp,
                'last_activity' => now()->subMinutes(5)->timestamp,
            ],
            minutes: 60,
        );

        $remaining = $this->service->getRemainingMinutes($user);
        $this->assertGreaterThan(700, $remaining); // ~12 hours
        $this->assertLessThan(730, $remaining);
    }

    /** @test */
    public function updates_last_activity_timestamp()
    {
        $user = User::factory()->create();
        $this->service->recordSessionStart($user, '127.0.0.1', 'Test');

        $before = Cache::get("session_user_{$user->id}")['last_activity'];

        sleep(1); // Ensure time passes
        $this->service->updateLastActivity($user);

        $after = Cache::get("session_user_{$user->id}")['last_activity'];
        $this->assertGreaterThan($before, $after);
    }
}
