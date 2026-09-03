<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Enums;

use App\Modules\Core\Contracts\LabelEnum;

enum InternshipGroupRole: string implements LabelEnum
{
    case STUDENT = 'student';
    case SCHOOL_TEACHER = 'school_teacher';
    case INDUSTRY_SUPERVISOR = 'industry_supervisor';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => __('common.enums.student'),
            self::SCHOOL_TEACHER => __('common.enums.school_teacher'),
            self::INDUSTRY_SUPERVISOR => __('common.enums.industry_supervisor'),
        };
    }
}
