<?php

declare(strict_types=1);

namespace App\Partners\Company\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Partners\Company\Data\CompanyData;
use App\Partners\Company\Events\CompanyUpdated;
use App\Partners\Company\Models\Company;

final class UpdateCompanyAction extends BaseCommandAction
{
    public function execute(Company $company, CompanyData $data): Company
    {
        return $this->transaction(function () use ($company, $data) {
            $company->update([
                'name' => $data->name,
                'address' => $data->address,
                'phone' => $data->phone,
                'email' => $data->email,
                'website' => $data->website,
                'description' => $data->description,
                'industry_sector' => $data->industrySector,
            ]);

            $this->log('company_updated', $company, ['name' => $company->name]);

            event(new CompanyUpdated($company));

            return $company;
        });
    }
}
