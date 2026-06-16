<?php

declare(strict_types=1);

namespace App\Guidance\Handbook\Enums;

use App\Core\Contracts\LabelEnum;

enum HandbookAudience: string implements LabelEnum
{
    case ALL = 'all';
    case STUDENT = 'student';
    case TEACHER = 'teacher';
    case SUPERVISOR = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::ALL => __('guidance.audience_all'),
            self::STUDENT => __('guidance.audience_student'),
            self::TEACHER => __('guidance.audience_teacher'),
            self::SUPERVISOR => __('guidance.audience_supervisor'),
        };
    }
}
