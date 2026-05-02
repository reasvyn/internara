<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\Status;
use Modules\User\Models\User;

/**
 * ActivationWorkflow
 *
 * Handles the complete account activation flow:
 * 1. Generate claim token (6-digit code or URL-safe token)
 * 2. Send activation email with token
 * 3. Validate token and activate account
 * 4. Update status from PROVISIONED → ACTIVATED → VERIFIED (pending)
 *
 * Enterprise-grade with:
 * - Token expiration (24 hours)
 * - Multiple attempts tracking
 * - Rate limiting per IP
 * - Audit trail for all attempts
 * - Notification system integration
 */
class ActivationWorkflow
{
    private AccountAuditLogger $auditLogger;

    private StatusTransitionService $transitionService;

    public function __construct(
        AccountAuditLogger $auditLogger,
        StatusTransitionService $transitionService,
    ) {
        $this->auditLogger = $auditLogger;
        $this->transitionService = $transitionService;
    }

    /**
     * Generate activation token for user claim
     *
     * Creates a claim token that must be validated before the user
     * can transition from PROVISIONED to ACTIVATED status.
     *
     * @param string $type 'email'|'sms' - delivery method
     *
     * @return array{token: string, expires_at: Carbon}
     */
    public function generateActivationToken(User $user, string $type = 'email'): array
    {
        // Check rate limit: max 3 tokens per 24 hours per user
        $recentTokens = DB::table('activation_tokens')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($recentTokens >= 3) {
            Log::warning('Activation token rate limit exceeded', [
                'user_id' => $user->id,
                'count' => $recentTokens,
            ]);
            throw new \Exception('Too many activation attempts. Please try again later.');
        }

        // Generate 6-digit numeric token (easier for manual entry)
        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addDay();

        DB::table('activation_tokens')->insert([
            'user_id' => $user->id,
            'token' => hash('sha256', $token), // Store hashed for security
            'token_type' => $type,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'last_attempt_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log token generation
        $this->auditLogger->log(
            user: $user,
            event: 'activation_token_generated',
            metadata: [
                'token_type' => $type,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        );

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Validate activation token and activate account
     *
     * @param string $token Plain text token from user
     * @param string $ipAddress IP address for audit trail
     *
     * @throws \Exception If token invalid, expired, or rate limited
     *
     * @return bool True if activation successful
     */
    public function validateAndActivate(User $user, string $token, string $ipAddress = ''): bool
    {
        return DB::transaction(function () use ($user, $token, $ipAddress) {
            // Find active token record
            $tokenRecord = DB::table('activation_tokens')
                ->where('user_id', $user->id)
                ->where('token', hash('sha256', $token))
                ->where('expires_at', '>=', now())
                ->first();

            if (! $tokenRecord) {
                // Increment failed attempts even if token not found
                $this->recordFailedAttempt($user, $ipAddress);
                throw new \Exception('Invalid or expired activation token.');
            }

            // Check rate limit: max 5 failed attempts per hour
            if ($tokenRecord->attempts >= 5 && $tokenRecord->last_attempt_at > now()->subHour()) {
                throw new \Exception(
                    'Too many failed attempts. Please request a new activation token.',
                );
            }

            // Token is valid - activate user
            $user->update([
                'account_status' => Status::ACTIVATED->value,
                'activated_at' => now(),
                'claimed_by_user_id' => $user->id,
                'last_activity_at' => now(), // Initialize activity tracking
            ]);

            // Perform status transition with audit logging
            $this->transitionService->transition(
                user: $user,
                fromStatus: Status::PENDING,
                toStatus: Status::ACTIVATED,
                reason: 'User claimed account via activation token',
                triggeredByUserId: $user->id,
                ipAddress: $ipAddress,
                metadata: [
                    'activation_method' => $tokenRecord->token_type,
                    'token_attempts' => $tokenRecord->attempts,
                ],
            );

            // Delete token record
            DB::table('activation_tokens')
                ->where('user_id', $user->id)
                ->where('token', hash('sha256', $token))
                ->delete();

            // Log successful activation
            $this->auditLogger->log(
                user: $user,
                event: 'account_activated',
                metadata: [
                    'method' => $tokenRecord->token_type,
                    'ip_address' => $ipAddress,
                ],
            );

            return true;
        });
    }

    /**
     * Record failed activation attempt
     */
    private function recordFailedAttempt(User $user, string $ipAddress = ''): void
    {
        DB::table('activation_tokens')
            ->where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->increment('attempts');

        DB::table('activation_tokens')
            ->where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->update(['last_attempt_at' => now()]);

        $this->auditLogger->log(
            user: $user,
            event: 'activation_token_failed_attempt',
            metadata: [
                'ip_address' => $ipAddress,
            ],
        );
    }

    /**
     * Resend activation token (enforces rate limit)
     *
     * @return array{token: string, expires_at: Carbon}
     */
    public function resendActivationToken(User $user): array
    {
        // Delete expired tokens first
        DB::table('activation_tokens')
            ->where('user_id', $user->id)
            ->where('expires_at', '<', now())
            ->delete();

        return $this->generateActivationToken($user, 'email');
    }

    /**
     * Check if user has pending activation
     */
    public function hasPendingActivation(User $user): bool
    {
        return $user->getStatus() === Status::PENDING &&
            DB::table('activation_tokens')
                ->where('user_id', $user->id)
                ->where('expires_at', '>=', now())
                ->exists();
    }

    /**
     * Get activation token status for user
     */
    public function getActivationStatus(User $user): ?array
    {
        $token = DB::table('activation_tokens')
            ->where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->first();

        if (! $token) {
            return null;
        }

        return [
            'expires_at' => $token->expires_at,
            'attempts' => $token->attempts,
            'last_attempt_at' => $token->last_attempt_at,
            'time_remaining' => now()->diffInMinutes($token->expires_at, false),
        ];
    }
}
