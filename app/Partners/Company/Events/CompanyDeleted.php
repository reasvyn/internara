<?php

declare(strict_types=1);

namespace App\Partners\Company\Events;

use App\Core\Events\BaseEvent;
use App\Partners\Company\Models\Company;

final class CompanyDeleted extends BaseEvent
{
    public function __construct(public Company $company) {}

    public function eventName(): string
    {
        return 'company.deleted';
    }
}
