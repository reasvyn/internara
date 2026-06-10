<?php

declare(strict_types=1);

namespace App\Setup\Data;

use App\Core\Data\BaseData;

final readonly class AdminData extends BaseData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
