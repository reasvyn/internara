<?php

declare(strict_types=1);

namespace App\Enums\Auth;

use App\Contracts\Shared\LabelEnum;

/**
 * Role enum with two role families:
 *
 * ── User Roles ──
 * Actual Spatie roles stored in database. Used for authentication,
 * route middleware (`role:`), policies, and permission assignment.
 *
 * ── Functional Roles ──
 * Contextual groupings for business logic within the internship
 * lifecycle. Used for phase-based operations, feature gating,
 * and domain-level decisions. NOT stored in database.
 *
 * Usage rules:
 * - Route middleware: use User Roles only
 * - Policy/Gate checks: use User Roles (or Functional Roles via resolvesTo())
 * - Business logic in Actions/Entities: prefer Functional Roles
 * - Dashboard/feature routing: use Functional Roles for grouping
 */
enum Role: string implements LabelEnum
{
    // ──────────────────────────────────────────────
    //  User Roles  (system-level, stored in DB)
    // ──────────────────────────────────────────────
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case SUPERVISOR = 'supervisor';

    // ──────────────────────────────────────────────
    //  Functional Roles  (contextual, not in DB)
    // ──────────────────────────────────────────────
    case MENTOR = 'func_mentor';
    case MENTEE = 'func_mentee';

    // ── Group Getters ──

    /** @return array<int, self> All user roles (system-level). */
    public static function userRoles(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::TEACHER,
            self::STUDENT,
            self::SUPERVISOR,
        ];
    }

    /** @return array<int, self> All user roles except SUPER_ADMIN. */
    public static function excludeSuperAdmin(): array
    {
        return [
            self::ADMIN,
            self::TEACHER,
            self::STUDENT,
            self::SUPERVISOR,
        ];
    }

    /** @return array<int, self> All user roles except SUPER_ADMIN and ADMIN. */
    public static function excludeAdmin(): array
    {
        return [
            self::TEACHER,
            self::STUDENT,
            self::SUPERVISOR,
        ];
    }

    /** @return array<int, self> All functional roles (contextual). */
    public static function functionalRoles(): array
    {
        return [
            self::ADMIN,
            self::MENTOR,
            self::MENTEE,
        ];
    }

    // ── Type Checks ──

    public function isUserRole(): bool
    {
        return in_array($this, self::userRoles(), true);
    }

    public function isFunctionalRole(): bool
    {
        return in_array($this, self::functionalRoles(), true);
    }

    // ── Resolution ──

    /**
     * Resolve functional role to underlying user roles.
     * For user roles, returns itself.
     *
     * @return array<int, self>
     */
    public function resolvesTo(): array
    {
        return match ($this) {
            self::ADMIN => [self::SUPER_ADMIN, self::ADMIN],
            self::MENTOR => [self::TEACHER, self::SUPERVISOR],
            self::MENTEE => [self::STUDENT],
            default => [$this],
        };
    }

    /**
     * Get all functional roles that a user role belongs to.
     *
     * @return array<int, self>
     */
    public static function functionalRolesFor(self $userRole): array
    {
        return match ($userRole) {
            self::SUPER_ADMIN, self::ADMIN => [self::ADMIN],
            self::TEACHER, self::SUPERVISOR => [self::MENTOR],
            self::STUDENT => [self::MENTEE],
        };
    }

    /**
     * Check if this role resolves to the given functional role.
     */
    public function is(self $functionalRole): bool
    {
        return in_array($this, $functionalRole->resolvesTo(), true);
    }

    // ── Label ──

    public function label(): string
    {
        return __("permission.role.{$this->value}");
    }
}
