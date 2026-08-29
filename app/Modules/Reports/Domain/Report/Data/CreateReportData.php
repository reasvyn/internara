<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Report\Data;

use App\Modules\Core\Data\BaseData;

final readonly class CreateReportData extends BaseData
{
    public function __construct(
        public string $registrationId,
    ) {}
}
