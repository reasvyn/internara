<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * System-wide user roles.
 */
enum Role: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case MENTOR = 'mentor';
    case STUDENT = 'student';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Administrator Sekolah',
            self::TEACHER => 'Guru Pembimbing',
            self::MENTOR => 'Pembimbing Industri',
            self::STUDENT => 'Siswa Magang',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'o-shield-check',
            self::ADMIN => 'o-user-group',
            self::TEACHER => 'o-academic-cap',
            self::MENTOR => 'o-briefcase',
            self::STUDENT => 'o-user',
        };
    }
}
