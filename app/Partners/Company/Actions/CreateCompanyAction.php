<?php

declare(strict_types=1);

namespace App\Partners\Company\Actions;

use App\Core\Actions\BaseAction;
use App\Partners\Company\Models\Company;

final class CreateCompanyAction extends BaseAction
{
    public function execute(array $data): Company
    {
        return $this->transaction(function () use ($data) {
            $company = Company::create($data);

            $this->log('company_created', $company, ['name' => $company->name]);

            return $company;
        });
    }
}
