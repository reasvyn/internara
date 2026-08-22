<?php

declare(strict_types=1);

namespace App\Journals\MonitoringVisit\Data;

use App\Core\Data\BaseData;

final readonly class CreateVisitData extends BaseData
{
    public function __construct(
        public string $teacherId,
        public string $registrationId,
        /** @var array{visit_date?: string, method: string, location?: string, duration_minutes?: int, notes?: string, student_condition?: string, company_feedback?: string, follow_up_actions?: string} */
        public array $data,
    ) {}
}
