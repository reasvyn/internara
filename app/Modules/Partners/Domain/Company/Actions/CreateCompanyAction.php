<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Company\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Partners\Domain\Company\Data\CompanyData;
use App\Modules\Partners\Domain\Company\Events\CompanyCreated;
use App\Modules\Partners\Domain\Company\Models\Company;

final class CreateCompanyAction extends BaseCommandAction
{
    public function execute(CompanyData $data): Company
    {
        return $this->transaction(function () use ($data) {
            $company = Company::create([
                'name' => $data->name,
                'address' => $data->address,
                'phone' => $data->phone,
                'email' => $data->email,
                'website' => $data->website,
                'description' => $data->description,
                'industry_sector' => $data->industrySector,
            ]);

            $this->dispatchEvent(new CompanyCreated($company));

            $this->log('company_created', $company, ['name' => $company->name]);

            return $company;
        });
    }
}
