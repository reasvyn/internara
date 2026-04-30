<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Status\Enums\AccountStatus;
use Modules\Status\Services\ActivationWorkflow;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * AccountActivationFeatureTest
 *
 * Integration tests for complete account activation workflow:
 * - Token generation
 * - Token validation
 * - Status transitions
 * - Rate limiting
 * - Error handling
 */
class AccountActivationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private ActivationWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = app(ActivationWorkflow::class);
    }

    /** @test */
    public function can_generate_activation_token()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        $result = $this->workflow->generateActivationToken($user, 'email');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertEquals(6, strlen($result['token']));
        $this->assertTrue(ctype_digit($result['token'])); // 6-digit numeric
    }

    /** @test */
    public function can_validate_and_activate_account()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        // Generate token
        $tokenResult = $this->workflow->generateActivationToken($user, 'email');
        $plainToken = $tokenResult['token'];

        // Validate and activate
        $success = $this->workflow->validateAndActivate($user, $plainToken, '127.0.0.1');

        $this->assertTrue($success);
        $this->assertTrue($user->refresh()->account_status === AccountStatus::ACTIVATED->value);
    }

    /** @test */
    public function prevents_invalid_token()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        $this->workflow->generateActivationToken($user, 'email');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid or expired');

        $this->workflow->validateAndActivate($user, '000000', '127.0.0.1');
    }

    /** @test */
    public function enforces_rate_limiting()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        // Generate max allowed tokens
        for ($i = 0; $i < 3; $i++) {
            $this->workflow->generateActivationToken($user, 'email');
        }

        // Fourth token should be blocked
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Too many activation attempts');

        $this->workflow->generateActivationToken($user, 'email');
    }

    /** @test */
    public function prevents_token_reuse()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        $tokenResult = $this->workflow->generateActivationToken($user, 'email');
        $plainToken = $tokenResult['token'];

        // First use succeeds
        $this->workflow->validateAndActivate($user, $plainToken, '127.0.0.1');

        // Reset user for second attempt
        $user->update(['account_status' => AccountStatus::PROVISIONED->value]);

        // Second use of same token fails
        $this->expectException(\Exception::class);

        $this->workflow->validateAndActivate($user, $plainToken, '127.0.0.1');
    }

    /** @test */
    public function tracks_failed_attempts()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        $this->workflow->generateActivationToken($user, 'email');

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->workflow->validateAndActivate($user, '000000', '127.0.0.1');
            } catch (\Exception $e) {
                // Expected
            }
        }

        // 6th attempt should be rate-limited
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Too many failed attempts');

        $this->workflow->validateAndActivate($user, '999999', '127.0.0.1');
    }

    /** @test */
    public function can_resend_activation_token()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        $result1 = $this->workflow->generateActivationToken($user, 'email');
        sleep(1); // Ensure time passes
        $result2 = $this->workflow->resendActivationToken($user);

        // Should be different tokens
        $this->assertNotEquals($result1['token'], $result2['token']);
    }

    /** @test */
    public function creates_activation_audit_trail()
    {
        $user = User::factory()->create([
            'account_status' => AccountStatus::PROVISIONED->value,
        ]);

        $tokenResult = $this->workflow->generateActivationToken($user, 'email');
        $this->workflow->validateAndActivate($user, $tokenResult['token'], '127.0.0.1');

        // Verify audit logs created
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => 'Modules\\User\\Models\\User',
            'subject_id' => $user->id,
            'event' => 'activation_token_generated',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => 'Modules\\User\\Models\\User',
            'subject_id' => $user->id,
            'event' => 'account_activated',
        ]);
    }
}
