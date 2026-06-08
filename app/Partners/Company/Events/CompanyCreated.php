<?php

declare(strict_types=1);

namespace App\Partners\Company\Events;

use App\Partners\Company\Models\Company;

final readonly class CompanyCreated
{
    public function __construct(
        public Company $company,
    ) {}
}