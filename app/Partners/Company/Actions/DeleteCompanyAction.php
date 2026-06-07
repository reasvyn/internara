<?php

declare(strict_types=1);

namespace App\Partners\Company\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Partners\Company\Models\Company;

final class DeleteCompanyAction extends BaseAction
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

            $company->delete();
        });
    }
}
