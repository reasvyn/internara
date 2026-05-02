<?php

declare(strict_types=1);

namespace Modules\Status\Enums;

/**
 * AccountStatus Enum
 *
 * Enterprise-grade account lifecycle states aligned with NIST SP 800-63B.
 * Defines the complete state machine for user account management with
 * clear semantics, behavioral impacts, and role-based constraints.
 *
 * @see https://pages.nist.gov/800-63-3/sp800-63b.html
 */
enum AccountStatus: string
{
    /**
     * PROVISIONED: Account record created, awaiting user claim
     *
     * Semantics: Initial state after admin creates account. Account exists
     * in system but user hasn't claimed or verified email yet.
     *
     * Access Level: No access
     * Behavioral Impact:
     * - Cannot log in
     * - No roles/permissions assigned
     * - Activation token generated (7-day expiry)
     * - Email unverified
     * - setup_required = true
     * - Visible only to school admin & creator
     *
     * Transitions: → ACTIVATED (user claims), → DISABLED (admin rejects)
     */
    case PROVISIONED = 'provisioned';

    /**
     * ACTIVATED: User claimed account, completed setup, awaiting admin verification
     *
     * Semantics: User has successfully claimed activation token and completed
     * setup wizard. Email verified. Account awaits explicit admin verification
     * or auto-verification after safeguard period.
     *
     * Access Level: Limited (read-only, view-only mode)
     * Behavioral Impact:
     * - Can log in (limited view-only access)
     * - Roles assigned but inactive
     * - Email verified
     * - setup_required = false
     * - Cannot create/edit records
     * - Notification sent to admin for verification
     * - Auto-verified after 24h if no issues detected
     *
     * Transitions: → VERIFIED (auto or admin), → SUSPENDED (admin rejects),
     *             → DISABLED (admin), max 30 days in this state
     */
    case ACTIVATED = 'activated';

    /**
     * VERIFIED: Account fully operational and trusted
     *
     * Semantics: Account has completed verification and is fully operational.
     * User has full access per role-based permissions. Default state for
     * normal operations.
     *
     * Access Level: Full access per role
     * Behavioral Impact:
     * - Can log in with full permissions
     * - All role-based permissions active
     * - Can perform all permitted actions
     * - Participates in all workflows
     * - Audit logging active
     * - Can transition to any state except PROTECTED
     *
     * Transitions: → RESTRICTED (access control), → SUSPENDED (security issue),
     *             → ARCHIVED (user exit)
     * Properties:
     * - verified_at: timestamp
     * - verified_by_user_id: who verified
     */
    case VERIFIED = 'verified';

    /**
     * PROTECTED: System-critical account, immutable status
     *
     * Semantics: Reserved exclusively for Super Admin accounts. System-critical
     * and permanently protected from modification. Cannot be changed, suspended,
     * or deleted. Prevents accidental lockout from system.
     *
     * Access Level: Full access (elevated privileges)
     * Behavioral Impact:
     * - Cannot be suspended/disabled/archived (system protection)
     * - Cannot have status changed to anything else
     * - Cannot have permissions revoked
     * - Cannot be created via UI (schema/migrations only)
     * - All actions require audit logging
     * - Password changes require 2FA verification
     * - Email changes require SMS/2FA verification
     * - Session timeout: 30 minutes inactivity
     * - Login activity dashboard required
     * - Multi-factor auth required
     *
     * Transitions: None (immutable)
     * Guard Rails:
     * - Cannot be demoted
     * - System warns if only one exists
     * - All changes require explicit confirmation
     * - Unusual activity generates alerts
     */
    case PROTECTED = 'protected';

    /**
     * RESTRICTED: Functional but access constrained
     *
     * Semantics: Account is functional but with limited access. Applied when
     * suspicious activity detected or admin manually restricts for investigation.
     * Can specify which modules/features/actions are restricted.
     *
     * Access Level: Conditional (specific modules/actions only)
     * Behavioral Impact:
     * - Can log in
     * - Cannot access restricted modules/features
     * - Certain actions require admin approval
     * - Cannot delegate permissions
     * - Rate limiting may apply
     * - Audit logging of all actions
     * - Auto-lifts restrictions after period or if behavior improves
     *
     * Transitions: → VERIFIED (restrictions lifted), → SUSPENDED (escalation),
     *             → ARCHIVED (permanent removal)
     * Properties:
     * - restrictions: array of restriction rules
     * - restricted_at: timestamp
     * - restricted_by_user_id: who applied
     * - restriction_reason: text explanation
     * - restriction_expires_at: optional auto-lift date
     * - restrictions JSON: [ { type: 'module', value: 'export' }, ... ]
     */
    case RESTRICTED = 'restricted';

    /**
     * SUSPENDED: Temporarily deactivated pending review
     *
     * Semantics: Account is temporarily inactive while admin/system reviews
     * account or investigates issue. Can be reactivated. Subject to timeout -
     * if not reviewed within max period, auto-archives.
     *
     * Access Level: No access
     * Behavioral Impact:
     * - Cannot log in
     * - No permissions
     * - Data preserved
     * - Can be reactivated
     * - Visible with warning badge
     * - Subject to max 90-day suspension
     * - Auto-archives if not reviewed after 90 days
     *
     * Transitions: → VERIFIED (admin lifts suspension), → ARCHIVED (permanent),
     *             → RESTRICTED (downgrade)
     * Properties:
     * - suspended_at: timestamp
     * - suspended_by_user_id: who suspended
     * - suspension_reason: required text
     * - suspension_expires_at: optional auto-unsuspend date
     */
    case SUSPENDED = 'suspended';

    /**
     * INACTIVE: Account unused for extended period
     *
     * Semantics: Dormancy detection state. Account is valid but hasn't logged
     * in for 6+ months. Can still log in (with warning) but flagged for
     * potential cleanup. Subject to auto-archival after 12 months total.
     *
     * Access Level: Can log in (reverts to VERIFIED on login)
     * Behavioral Impact:
     * - Can still log in but with warning
     * - User prompted to confirm account use
     * - Subject to auto-archive after 12 months
     * - May have permissions temporarily disabled
     * - Periodic notifications sent
     * - Visible to admin as "stale account"
     *
     * Transitions: → VERIFIED (user logs in), → ARCHIVED (admin archives after 12mo),
     *             → SUSPENDED (admin action)
     * Properties:
     * - last_activity_at: timestamp
     * - inactivity_warning_sent_at: timestamp
     * - inactivity_warning_count: int
     */
    case INACTIVE = 'inactive';

    /**
     * ARCHIVED: Logically deleted, retained for compliance
     *
     * Semantics: Account logically deleted. Data preserved for audit/compliance
     * for 7 years. Cannot be reactivated. Final state. Not visible in normal
     * views, only in audit/compliance views.
     *
     * Access Level: No access (historical only)
     * Behavioral Impact:
     * - Cannot log in
     * - No permissions
     * - Data retained for 7 years (GDPR/CCPA compliant)
     * - Visible in audit/compliance views only
     * - Cannot be reactivated (must create new account)
     * - May be soft-deleted or masked after 1 year
     * - Permanently purged after 7 years
     *
     * Transitions: None (final state)
     * Properties:
     * - archived_at: timestamp
     * - archived_by_user_id: who archived
     * - archival_reason: enum (user_exit, policy_violation, data_retention_only, etc.)
     * - scheduled_deletion_at: when data will be purged
     */
    case ARCHIVED = 'archived';

    /**
     * Get the color/badge variant for this status.
     * Used in UI to provide visual distinction.
     */
    public function color(): string
    {
        return match ($this) {
            self::PROVISIONED => 'warning', // ⏳ Yellow - waiting for action
            self::ACTIVATED => 'info', // 🔵 Blue - almost ready
            self::VERIFIED => 'success', // 🟢 Green - fully active
            self::PROTECTED => 'primary', // 🔒 Blue - system-protected
            self::RESTRICTED => 'warning', // ⚠️  Orange - limited access
            self::SUSPENDED => 'error', // 🔴 Red - no access
            self::INACTIVE => 'warning', // ⏳ Yellow - dormant
            self::ARCHIVED => 'error', // 📦 Red - historical only
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
     * Get the translation key for this status description.
     */
    public function description(): string
    {
        return 'account_status.description.'.$this->value;
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
     *
     * @return array<self> List of states this status can transition to
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PROVISIONED => [self::ACTIVATED, self::SUSPENDED],
            self::ACTIVATED => [self::VERIFIED, self::SUSPENDED, self::ARCHIVED],
            self::VERIFIED => [self::RESTRICTED, self::SUSPENDED, self::ARCHIVED, self::INACTIVE],
            self::PROTECTED => [], // Cannot transition
            self::RESTRICTED => [self::VERIFIED, self::SUSPENDED, self::ARCHIVED],
            self::SUSPENDED => [self::VERIFIED, self::ARCHIVED],
            self::INACTIVE => [self::VERIFIED, self::ARCHIVED, self::SUSPENDED],
            self::ARCHIVED => [], // Final state
        };
    }

    /**
     * Check if transition from current to target status is valid.
     */
    public function canTransitionTo(self $target): bool
    {
        // No transitions from/to PROTECTED or ARCHIVED
        if ($this->isTerminal() || $target->isTerminal()) {
            return false;
        }

        return in_array($target, $this->validTransitions(), strict: true);
    }

    /**
     * Get all cases in logical lifecycle order.
     *
     * @return array<self>
     */
    public static function lifecycle(): array
    {
        return [
            self::PROVISIONED,
            self::ACTIVATED,
            self::VERIFIED,
            self::RESTRICTED,
            self::SUSPENDED,
            self::INACTIVE,
            self::ARCHIVED,
            self::PROTECTED, // Special - not in normal lifecycle
        ];
    }

    /**
     * Get active states (where user can access system).
     *
     * @return array<self>
     */
    public static function activeStates(): array
    {
        return [self::VERIFIED, self::PROTECTED, self::RESTRICTED, self::INACTIVE];
    }

    /**
     * Get inactive states (where user cannot access system).
     *
     * @return array<self>
     */
    public static function inactiveStates(): array
    {
        return [self::PROVISIONED, self::ACTIVATED, self::SUSPENDED, self::ARCHIVED];
    }
}
