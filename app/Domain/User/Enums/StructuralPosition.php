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
            self::PRINCIPAL => 'Principal',
            self::VICE_PRINCIPAL => 'Vice Principal',
            self::HEAD_OF_DEPARTMENT => 'Head of Department',
            self::PROGRAM_COORDINATOR => 'Program Coordinator',
            self::SUPERVISING_TEACHER => 'Supervising Teacher',
            self::INDUSTRY_SUPERVISOR => 'Industry Supervisor',
        };
    }
}
