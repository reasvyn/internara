<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * System-wide user roles.
 */
enum Role: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case MENTOR = 'mentor';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return __("permission::role.{$this->value}");
    }
}
