<?php

declare(strict_types=1);

namespace App\Enrollment\AccountApplication\Data;

use App\Core\Data\BaseData;

final readonly class RejectAccountApplicationData extends BaseData
{
    public function __construct(
        public string $applicationId,
        public string $reason,
    ) {}
}
