<?php

declare(strict_types=1);

namespace App\Enums\Auth;

use App\Contracts\Shared\LabelEnum;

/**
 * Enum Role
 *
 * Authoritative identifiers for system-wide user roles.
 */
enum Role: string implements LabelEnum
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case SUPERVISOR = 'supervisor';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return __("permission::role.{$this->value}");
    }
}
