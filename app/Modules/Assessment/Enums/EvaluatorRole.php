<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Enums;

use App\Modules\Core\Contracts\LabelEnum;

enum EvaluatorRole: string implements LabelEnum
{
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case SUPERVISOR = 'supervisor';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => __('common.enums.admin'),
            self::TEACHER => __('common.enums.teacher'),
            self::SUPERVISOR => __('common.enums.industry_supervisor'),
            self::SYSTEM => __('common.enums.system_auto'),
        };
    }
}
