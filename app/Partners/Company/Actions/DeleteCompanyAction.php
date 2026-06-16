<?php

declare(strict_types=1);

namespace App\Partners\Company\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Partners\Company\Events\CompanyDeleted;
use App\Partners\Company\Models\Company;

final class DeleteCompanyAction extends BaseCommandAction
{
    public function execute(Company $company): void
    {
        if ($company->placements()->count() > 0 || $company->partnerships()->count() > 0) {
            throw new RejectedException(
                'Cannot delete company with existing placements or partnerships.',
            );
        }

        $this->transaction(function () use ($company) {
            $this->log('company_deleted', $company, ['name' => $company->name]);

            event(new CompanyDeleted($company));

            $company->delete();
        });
    }
}
