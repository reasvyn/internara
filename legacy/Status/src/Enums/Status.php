<?php

declare(strict_types=1);

namespace Modules\Status\Enums;

/**
 * Enum Status
 *
 * Enterprise account lifecycle states following NIST SP 800-63B guidelines.
 * 8 states covering account creation, verification, active use, and archival.
 *
 * States:
 * - PENDING: Account created but not yet activated (awaiting verification)
 * - ACTIVATED: Account activated but not yet verified (user claimed it)
 * - VERIFIED: Account verified and fully active (approved by authority)
 * - PROTECTED: Super Admin account - immutable and protected
 * - RESTRICTED: Account restricted - limited functionality (suspicious activity)
 * - SUSPENDED: Account suspended - no access (policy violation)
 * - INACTIVE: Account inactive - no login for 180+ days (auto-archived)
 * - ARCHIVED: Account archived - permanently inactive (GDPR deletion pending)
 *
 * Transition Rules:
 * - PENDING → ACTIVATED (user action) or → VERIFIED (auto-approve)
 * - ACTIVATED → VERIFIED (authority approves) or → RESTRICTED/SUSPENDED
 * - VERIFIED → RESTRICTED/SUSPENDED (policy violation) or → INACTIVE (no use 180d)
 * - RESTRICTED → VERIFIED (after review) or → SUSPENDED
 * - SUSPENDED → RESTRICTED (appeal) or → INACTIVE
 * - INACTIVE → ARCHIVED (auto, after 90d more) or → VERIFIED (reactivate)
 * - ARCHIVED → Cannot transition (permanent)
 * - PROTECTED → Cannot transition (Super Admin immutable)
 */
enum Status: string
{
    // Lifecycle states
    case PENDING = 'pending'; // Account created, awaiting activation
    case ACTIVATED = 'activated'; // User claimed account, awaiting verification
    case VERIFIED = 'verified'; // Account verified and fully operational
    case PROTECTED = 'protected'; // Super Admin - immutable
    case RESTRICTED = 'restricted'; // Limited access (investigation/review)
    case SUSPENDED = 'suspended'; // No access (policy violation)
    case INACTIVE = 'inactive'; // No login for 180+ days
    case ARCHIVED = 'archived'; // Permanent inactivity, GDPR pending

    /**
     * Get the visual color associated with the status.
     * Used in UI components (badges, buttons, etc).
     */
    public function color(): string
    {
        return match ($this) {
            // Neutral states
            self::PENDING => '#f59e0b', // Amber - waiting for action
            self::ACTIVATED => '#3b82f6', // Blue - in progress

            // Active states
            self::VERIFIED => '#10b981', // Green - fully active & verified
            self::PROTECTED => '#8b5cf6', // Purple - special/protected

            // Problem states
            self::RESTRICTED => '#f97316', // Orange - limited access
            self::SUSPENDED => '#ef4444', // Red - no access

            // End of life
            self::INACTIVE => '#6b7280', // Gray - dormant
            self::ARCHIVED => '#1f2937', // Dark gray - archived
        };
    }

    /**
     * Get the human-readable label in context of user's language.
     * Keys correspond to language files in resources/lang/status/
     */
    public function label(): string
    {
        return 'status::status.'.$this->value;
    }

    /**
     * Get detailed description of what this status means.
     */
    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Akun telah dibuat. Menunggu untuk diaktifkan oleh pemilik akun.',
            self::ACTIVATED => 'Akun telah diaktifkan oleh pemilik. Menunggu verifikasi dari admin/pengajar.',
            self::VERIFIED => 'Akun terverifikasi dan dapat digunakan secara penuh.',
            self::PROTECTED => 'Akun Super Admin terlindungi - tidak dapat diubah atau dihapus.',
            self::RESTRICTED => 'Akun dalam pembatasan sementara. Fungsionalitas terbatas selama investigasi.',
            self::SUSPENDED => 'Akun ditangguhkan - tidak memiliki akses ke sistem.',
            self::INACTIVE => 'Akun tidak aktif - tidak ada login selama 180+ hari.',
            self::ARCHIVED => 'Akun diarsipkan permanen - menunggu penghapusan data sesuai GDPR.',
        };
    }

    /**
     * Get valid transitions FROM this status.
     * Returns array of Status cases that can be transitioned to.
     *
     * Transition matrix:
     * - PENDING → ACTIVATED, VERIFIED, SUSPENDED
     * - ACTIVATED → VERIFIED, RESTRICTED, SUSPENDED
     * - VERIFIED → RESTRICTED, SUSPENDED, INACTIVE
     * - PROTECTED → (none - immutable)
     * - RESTRICTED → VERIFIED, SUSPENDED
     * - SUSPENDED → RESTRICTED, INACTIVE
     * - INACTIVE → ARCHIVED, VERIFIED (reactivate)
     * - ARCHIVED → (none - final state)
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::ACTIVATED, self::VERIFIED, self::SUSPENDED],
            self::ACTIVATED => [self::VERIFIED, self::RESTRICTED, self::SUSPENDED],
            self::VERIFIED => [self::RESTRICTED, self::SUSPENDED, self::INACTIVE],
            self::PROTECTED => [], // Immutable
            self::RESTRICTED => [self::VERIFIED, self::SUSPENDED],
            self::SUSPENDED => [self::RESTRICTED, self::INACTIVE],
            self::INACTIVE => [
                self::ARCHIVED,
                self::VERIFIED, // Reactivate
            ],
            self::ARCHIVED => [], // Final state
        };
    }

    /**
     * Check if can transition to another status.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->validTransitions());
    }

    /**
     * Check if this is an active/usable status.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::VERIFIED, self::PROTECTED, self::ACTIVATED]);
    }

    /**
     * Check if this is a problem/restricted status.
     */
    public function isProblem(): bool
    {
        return in_array($this, [self::RESTRICTED, self::SUSPENDED]);
    }

    /**
     * Check if this is an end-of-life status (cannot be reversed).
     */
    public function isEndOfLife(): bool
    {
        return in_array($this, [self::ARCHIVED]);
    }

    /**
     * Check if this is the protected Super Admin status.
     */
    public function isProtected(): bool
    {
        return $this === self::PROTECTED;
    }
}
