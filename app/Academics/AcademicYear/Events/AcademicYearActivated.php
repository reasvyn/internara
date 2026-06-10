<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Events;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Events\BaseEvent;

final class AcademicYearActivated extends BaseEvent
{
    public function __construct(
        public AcademicYear $academicYear,
        public ?AcademicYear $previousActive = null,
    ) {}

    public function eventName(): string
    {
        return 'academic_year.activated';
    }
}
