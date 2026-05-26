<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum StructuralPosition: string implements LabelEnum
{
    case PRINCIPAL = 'principal';
    case VICE_PRINCIPAL = 'vice_principal';
    case HEAD_OF_DEPARTMENT = 'head_of_department';
    case PROGRAM_COORDINATOR = 'program_coordinator';
    case SUPERVISING_TEACHER = 'supervising_teacher';
    case INDUSTRY_SUPERVISOR = 'industry_supervisor';

    public function label(): string
    {
        return match ($this) {
            self::PRINCIPAL => __('user.structural.principal'),
            self::VICE_PRINCIPAL => __('user.structural.vice_principal'),
            self::HEAD_OF_DEPARTMENT => __('user.structural.head_of_department'),
            self::PROGRAM_COORDINATOR => __('user.structural.program_coordinator'),
            self::SUPERVISING_TEACHER => __('user.structural.supervising_teacher'),
            self::INDUSTRY_SUPERVISOR => __('user.structural.industry_supervisor'),
        };
    }
}
