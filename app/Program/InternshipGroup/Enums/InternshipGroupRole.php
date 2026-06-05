<?php

declare(strict_types=1);

namespace App\Program\InternshipGroup\Enums;

use App\Core\Contracts\LabelEnum;

enum InternshipGroupRole: string implements LabelEnum
{
    case STUDENT = 'student';
    case SCHOOL_TEACHER = 'school_teacher';
    case INDUSTRY_SUPERVISOR = 'industry_supervisor';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => __('Student'),
            self::SCHOOL_TEACHER => __('School Teacher'),
            self::INDUSTRY_SUPERVISOR => __('Industry Supervisor'),
        };
    }
}
