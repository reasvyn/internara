<?php

declare(strict_types=1);

namespace App\Partners\Company\Data;

use App\Core\Data\BaseData;

final readonly class CompanyData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $address = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $website = null,
        public ?string $description = null,
        public ?string $industrySector = null,
    ) {}
}