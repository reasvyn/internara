<?php

declare(strict_types=1);

namespace App\Evaluation\Core\Enums;

use App\Core\Contracts\LabelEnum;

enum EvaluatorRole: string implements LabelEnum
{
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case SUPERVISOR = 'supervisor';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => __('Admin'),
            self::TEACHER => __('Teacher'),
            self::SUPERVISOR => __('Industry Supervisor'),
            self::SYSTEM => __('System (Auto)'),
        };
    }
}
