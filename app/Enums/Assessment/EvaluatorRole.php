<?php

declare(strict_types=1);

namespace App\Enums\Assessment;

use App\Contracts\Shared\LabelEnum;

enum EvaluatorRole: string implements LabelEnum
{
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case SUPERVISOR = 'supervisor';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::TEACHER => 'Teacher',
            self::SUPERVISOR => 'Industry Supervisor',
            self::SYSTEM => 'System (Auto)',
        };
    }
}
