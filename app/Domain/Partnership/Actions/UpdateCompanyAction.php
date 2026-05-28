<?php

declare(strict_types=1);

namespace App\Domain\Partnership\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Partnership\Models\Company;

final class UpdateCompanyAction extends BaseAction
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
