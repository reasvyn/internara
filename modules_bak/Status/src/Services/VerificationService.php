<?php

declare(strict_types=1);

namespace Modules\Status\Services;

use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\Status;
use Modules\User\Models\User;

class VerificationService
{
    private const EMAIL_VERIFICATION_EXPIRES_HOURS = 24;

    public function __construct(private StatusTransitionService $statusTransition) {}

    /**
     * Verify email address and auto-transition account if ready.
     *
     * Flow:
     * 1. Mark email as verified
     * 2. If user is PROVISIONED, transition to ACTIVATED
     * 3. After 24h with email verified, auto-transition to VERIFIED
     */
    public function verifyEmail(User $user, ?User $verifiedBy = null): void
    {
        $user->email_verified_at = now();
        $user->save();

        Log::info('Email verified for user', [
            'user_id' => $user->id,
            'verified_by' => $verifiedBy?->id ?? 'user_action',
        ]);

        // If user is PROVISIONED, auto-activate
        if ($user->getStatus() === Status::PENDING) {
            try {
                $this->statusTransition->transition(
                    user: $user,
                    newStatus: Status::ACTIVATED,
                    reason: 'Email verified - auto-activated',
                    triggeredBy: null,
                    userAgent: 'System/EmailVerification',
                );
            } catch (\Exception $e) {
                Log::warning('Failed to auto-activate on email verification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Manually verify account (admin action).
     * Transitions from ACTIVATED → VERIFIED.
     */
    public function verifyAccountManually(
        User $user,
        User $verifiedBy,
        ?string $reason = null,
    ): void {
        try {
            $this->statusTransition->transition(
                user: $user,
                newStatus: Status::VERIFIED,
                reason: $reason ?? 'Manually verified by administrator',
                triggeredBy: $verifiedBy,
                userAgent: 'Admin/ManualVerification',
            );

            Log::info('Account manually verified', [
                'user_id' => $user->id,
                'verified_by' => $verifiedBy->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to manually verify account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if user's email is verified.
     */
    public function isEmailVerified(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    /**
     * Check if enough time has passed since email verification for auto-promotion to VERIFIED.
     */
    public function isReadyForAutoVerification(User $user): bool
    {
        if (!$this->isEmailVerified($user)) {
            return false;
        }

        // Email must be verified for 24 hours
        $verifiedAt = $user->email_verified_at;
        $now = now();

        return $verifiedAt
            ->addHours(self::EMAIL_VERIFICATION_EXPIRES_HOURS)
            ->lessThanOrEqualTo($now);
    }

    /**
     * Get hours remaining before auto-verification.
     */
    public function getHoursUntilAutoVerification(User $user): int
    {
        if (!$this->isEmailVerified($user)) {
            return -1; // Not verified yet
        }

        if ($this->isReadyForAutoVerification($user)) {
            return 0;
        }

        $verifiedAt = $user->email_verified_at;
        $autoVerifyAt = $verifiedAt->addHours(self::EMAIL_VERIFICATION_EXPIRES_HOURS);

        return max(0, now()->diffInHours($autoVerifyAt, absolute: false));
    }

    /**
     * Multi-factor authentication: Mark MFA as verified.
     * (Implementation details depend on your MFA strategy - TOTP, SMS, etc.)
     */
    public function completeMfaVerification(User $user, string $method = 'totp'): void
    {
        $verificationMethods = $user->verification_metadata['mfa_methods'] ?? [];
        $verificationMethods[$method] = [
            'verified_at' => now()->toIso8601String(),
            'expires_at' => now()->addDays(30)->toIso8601String(),
        ];

        $metadata = $user->verification_metadata ?? [];
        $metadata['mfa_methods'] = $verificationMethods;

        $user->verification_metadata = $metadata;
        $user->save();

        Log::info('MFA verification completed', [
            'user_id' => $user->id,
            'method' => $method,
        ]);
    }

    /**
     * Check if user has completed MFA verification.
     */
    public function hasMfaVerified(User $user, string $method = 'totp'): bool
    {
        $mfaMethods = $user->verification_metadata['mfa_methods'] ?? [];

        if (!isset($mfaMethods[$method])) {
            return false;
        }

        $expiresAt = $mfaMethods[$method]['expires_at'] ?? null;

        if ($expiresAt && now()->greaterThan($expiresAt)) {
            return false;
        }

        return true;
    }

    /**
     * Get all verification statuses for a user.
     */
    public function getVerificationStatus(User $user): array
    {
        return [
            'email_verified' => $this->isEmailVerified($user),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'ready_for_auto_verification' => $this->isReadyForAutoVerification($user),
            'hours_until_auto_verify' => $this->getHoursUntilAutoVerification($user),
            'mfa_verified' => $this->hasMfaVerified($user),
            'current_account_status' => $user->getStatus()?->value,
        ];
    }
}
