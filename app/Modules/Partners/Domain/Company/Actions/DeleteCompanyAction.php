<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Company\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partners\Domain\Company\Events\CompanyDeleted;
use App\Modules\Partners\Domain\Company\Models\Company;

final class DeleteCompanyAction extends BaseCommandAction
{
    public function execute(Company $company): void
    {
        if ($company->placements()->exists() || $company->partnerships()->exists()) {
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
