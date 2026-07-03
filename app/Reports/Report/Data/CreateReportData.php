<?php

declare(strict_types=1);

namespace App\Reports\Report\Data;

use App\Core\Data\BaseData;

final readonly class CreateReportData extends BaseData
{
    public function __construct(
        public string $registrationId,
    ) {}
}
