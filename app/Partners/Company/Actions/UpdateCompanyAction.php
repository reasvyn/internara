<?php

declare(strict_types=1);

namespace App\Partners\Company\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Partners\Company\Models\Company;

final class UpdateCompanyAction extends BaseCommandAction
{
    public function execute(Company $company, array $data): Company
    {
        return $this->transaction(function () use ($company, $data) {
            $company->update($data);

            $this->log('company_updated', $company, ['name' => $company->name]);

            return $company;
        });
    }
}
