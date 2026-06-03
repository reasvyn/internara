<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum Role: string implements LabelEnum
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case SUPERVISOR = 'supervisor';

    case MENTOR = 'func_mentor';
    case MENTEE = 'func_mentee';

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

    public static function excludeSuperAdmin(): array
    {
        return [
            self::ADMIN,
            self::TEACHER,
            self::STUDENT,
            self::SUPERVISOR,
        ];
    }

    public static function excludeAdmin(): array
    {
        return [
            self::TEACHER,
            self::STUDENT,
            self::SUPERVISOR,
        ];
    }

    public static function functionalRoles(): array
    {
        return [
            self::ADMIN,
            self::MENTOR,
            self::MENTEE,
        ];
    }

    public function isUserRole(): bool
    {
        return in_array($this, self::userRoles(), true);
    }

    public function isFunctionalRole(): bool
    {
        return in_array($this, self::functionalRoles(), true);
    }

    public function resolvesTo(): array
    {
        return match ($this) {
            self::ADMIN => [self::SUPER_ADMIN, self::ADMIN],
            self::MENTOR => [self::TEACHER, self::SUPERVISOR],
            self::MENTEE => [self::STUDENT],
            default => [$this],
        };
    }

    public static function functionalRolesFor(self $userRole): array
    {
        return match ($userRole) {
            self::SUPER_ADMIN, self::ADMIN => [self::ADMIN],
            self::TEACHER, self::SUPERVISOR => [self::MENTOR],
            self::STUDENT => [self::MENTEE],
            default => [],
        };
    }

    public function is(self $functionalRole): bool
    {
        return in_array($this, $functionalRole->resolvesTo(), true);
    }

    public function label(): string
    {
        return __("permission.role.{$this->value}");
    }
}
