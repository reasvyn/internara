<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Data;

use App\Core\Data\BaseData;

final readonly class PartnershipData extends BaseData
{
    public function __construct(
        public string $companyId,
        public string $agreementNumber,
        public string $title,
        public string $startDate,
        public string $endDate,
        public ?string $scope = null,
        public ?string $contactPersonName = null,
        public ?string $contactPersonPhone = null,
        public ?string $contactPersonEmail = null,
        public ?string $signedBySchool = null,
        public ?string $signedByCompany = null,
        public ?string $signedAt = null,
        public ?string $notes = null,
    ) {}
}