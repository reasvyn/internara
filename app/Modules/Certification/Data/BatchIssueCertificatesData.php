<?php

declare(strict_types=1);

namespace App\Modules\Certification\Data;

use App\Modules\Core\Data\BaseData;

final readonly class BatchIssueCertificatesData extends BaseData
{
    public function __construct(
        public array $registrationIds,
        public string $status,
        public string $templateId,
    ) {}
}
