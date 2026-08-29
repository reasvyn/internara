<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Enums;

use App\Modules\Core\Contracts\LabelEnum;

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
