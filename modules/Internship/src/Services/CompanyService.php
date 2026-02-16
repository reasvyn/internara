<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Internship\Models\Company;
use Modules\Internship\Services\Contracts\CompanyService as Contract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class CompanyService
 *
 * Handles the business logic for industry partner master data.
 */
class CompanyService extends EloquentQuery implements Contract
{
    /**
     * CompanyService constructor.
     */
    public function __construct(Company $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'business_field', 'email']);
        $this->setSortable(['name', 'created_at']);
    }
}
