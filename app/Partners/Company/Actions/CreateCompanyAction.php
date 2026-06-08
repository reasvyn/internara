<?php

declare(strict_types=1);

namespace App\Partners\Company\Actions;

use App\Core\Actions\BaseAction;
use App\Partners\Company\Data\CompanyData;
use App\Partners\Company\Events\CompanyCreated;
use App\Partners\Company\Models\Company;
use Illuminate\Support\Facades\Event;

final class CreateCompanyAction extends BaseAction
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

            Event::dispatch(new CompanyCreated($company));

            $this->log('company_created', $company, ['name' => $company->name]);

            return $company;
        });
    }
}
