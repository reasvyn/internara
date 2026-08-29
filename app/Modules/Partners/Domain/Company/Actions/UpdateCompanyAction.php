<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Company\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Partners\Domain\Company\Data\CompanyData;
use App\Modules\Partners\Domain\Company\Events\CompanyUpdated;
use App\Modules\Partners\Domain\Company\Models\Company;

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
