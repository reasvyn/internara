<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\AcademicYear\Events;

use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Events\BaseEvent;

final class AcademicYearUpdated extends BaseEvent
{
    public function __construct(public AcademicYear $academicYear) {}

    public function eventName(): string
    {
        return 'academic_year.updated';
    }
}
