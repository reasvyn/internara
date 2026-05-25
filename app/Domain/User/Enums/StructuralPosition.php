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
            self::PRINCIPAL => __('Principal'),
            self::VICE_PRINCIPAL => __('Vice Principal'),
            self::HEAD_OF_DEPARTMENT => __('Head of Department'),
            self::PROGRAM_COORDINATOR => __('Program Coordinator'),
            self::SUPERVISING_TEACHER => __('Supervising Teacher'),
            self::INDUSTRY_SUPERVISOR => __('Industry Supervisor'),
        };
    }
}
