<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Events;

use App\Academics\AcademicYear\Models\AcademicYear;

final readonly class AcademicYearCreated
{
    public function __construct(
        public AcademicYear $academicYear,
    ) {}
}