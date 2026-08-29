<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Data;

use App\Modules\Core\Data\BaseData;

final readonly class CreateStudentReportData extends BaseData
{
    public function __construct(
        public string $registrationId,
    ) {}
}
