<?php

declare(strict_types=1);

namespace App\Setup\Data;

use App\Core\Data\BaseData;

final readonly class SchoolData extends BaseData
{
    public function __construct(
        public string $name,
        public string $institutionalCode = '',
        public string $email = '',
        public string $address = '',
        public string $phone = '',
        public string $website = '',
        public string $principalName = '',
    ) {}
}