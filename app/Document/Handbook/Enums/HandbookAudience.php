<?php

declare(strict_types=1);

namespace App\Document\Handbook\Enums;

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
            self::ALL => __('handbook.audience_all'),
            self::STUDENT => __('handbook.audience_student'),
            self::TEACHER => __('handbook.audience_teacher'),
            self::SUPERVISOR => __('handbook.audience_supervisor'),
        };
    }
}
