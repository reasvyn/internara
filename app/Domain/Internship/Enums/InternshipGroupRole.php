<?php

declare(strict_types=1);

namespace App\Domain\Internship\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum InternshipGroupRole: string implements LabelEnum
{
    case STUDENT = 'student';
    case SCHOOL_TEACHER = 'school_teacher';
    case INDUSTRY_SUPERVISOR = 'industry_supervisor';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Student',
            self::SCHOOL_TEACHER => 'School Teacher',
            self::INDUSTRY_SUPERVISOR => 'Industry Supervisor',
        };
    }
}
