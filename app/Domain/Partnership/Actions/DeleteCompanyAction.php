<?php

declare(strict_types=1);

namespace App\Domain\Partnership\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Partnership\Models\Company;

class DeleteCompanyAction extends BaseAction
{
    public function execute(Company $company): void
    {
        if ($company->placements()->count() > 0 || $company->partnerships()->count() > 0) {
            throw new RejectedException('Cannot delete company with existing placements or partnerships.');
        }

        $this->transaction(function () use ($company) {
            $this->log('company_deleted', $company, ['name' => $company->name]);

            $company->delete();
        });
    }
}
