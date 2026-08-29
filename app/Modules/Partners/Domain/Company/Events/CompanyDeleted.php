<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Company\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Partners\Domain\Company\Models\Company;

final class CompanyDeleted extends BaseEvent
{
    public function __construct(public Company $company) {}

    public function eventName(): string
    {
        return 'company.deleted';
    }
}
