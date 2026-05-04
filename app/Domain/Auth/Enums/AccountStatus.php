<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

/**
 * AccountStatus Enum
 *
 * Enterprise-grade account lifecycle states aligned with NIST SP 800-63B.
 * Defines the complete state machine for user account management with
 * clear semantics, behavioral impacts, and role-based constraints.
 *
 * S1 - Secure: Implements login allowance checks and transition rules.
 * S2 - Sustain: Visual mapping (colors) and descriptions for UI.
 */
enum AccountStatus: string
{
    /**
     * PROVISIONED: Account record created, awaiting user claim.
     */
    case PROVISIONED = 'provisioned';

    /**
     * ACTIVATED: User claimed account, completed setup, awaiting admin verification.
     */
    case ACTIVATED = 'activated';

    /**
     * VERIFIED: Account fully operational and trusted.
     */
    case VERIFIED = 'verified';

    /**
     * PROTECTED: System-critical account (Super Admin), immutable status.
     */
    case PROTECTED = 'protected';

    /**
     * RESTRICTED: Functional but access constrained.
     */
    case RESTRICTED = 'restricted';

    /**
     * SUSPENDED: Temporarily deactivated pending review.
     */
    case SUSPENDED = 'suspended';

    /**
     * INACTIVE: Account unused for extended period.
     */
    case INACTIVE = 'inactive';

    /**
     * ARCHIVED: Logically deleted, retained for compliance.
     */
    case ARCHIVED = 'archived';

    /**
     * Get the color/badge variant for this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::PROVISIONED => 'warning',
            self::ACTIVATED => 'info',
            self::VERIFIED => 'success',
            self::PROTECTED => 'primary',
            self::RESTRICTED => 'warning',
            self::SUSPENDED => 'error',
            self::INACTIVE => 'warning',
            self::ARCHIVED => 'error',
        };
    }

    /**
     * Get the translation key for this status label.
     */
    public function label(): string
    {
        return 'account_status.status.'.$this->value;
    }

    /**
     * Check if this status allows login.
     */
    public function allowsLogin(): bool
    {
        return match ($this) {
            self::PROVISIONED => false,
            self::ACTIVATED => true, // Limited mode
            self::VERIFIED => true, // Full access
            self::PROTECTED => true, // Full access
            self::RESTRICTED => true, // Conditional
            self::SUSPENDED => false,
            self::INACTIVE => true, // Can log in (with warning)
            self::ARCHIVED => false,
        };
    }

    /**
     * Check if this status is final/terminal (cannot be changed).
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::ARCHIVED => true,
            self::PROTECTED => true,
            default => false,
        };
    }

    /**
     * Get valid transitions FROM this status.
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PROVISIONED => [self::ACTIVATED, self::SUSPENDED],
            self::ACTIVATED => [self::VERIFIED, self::SUSPENDED, self::ARCHIVED],
            self::VERIFIED => [self::RESTRICTED, self::SUSPENDED, self::ARCHIVED, self::INACTIVE],
            self::PROTECTED => [],
            self::RESTRICTED => [self::VERIFIED, self::SUSPENDED, self::ARCHIVED],
            self::SUSPENDED => [self::VERIFIED, self::ARCHIVED],
            self::INACTIVE => [self::VERIFIED, self::ARCHIVED, self::SUSPENDED],
            self::ARCHIVED => [],
        };
    }

    /**
     * Check if transition from current to target status is valid.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this->isTerminal() || $target->isTerminal()) {
            return false;
        }

        return in_array($target, $this->validTransitions(), strict: true);
    }
}
