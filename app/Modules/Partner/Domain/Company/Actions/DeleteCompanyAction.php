<?php

declare(strict_types=1);

namespace App\Modules\Partner\Domain\Company\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Partner\Domain\Company\Events\CompanyDeleted;
use App\Modules\Partner\Domain\Company\Models\Company;

final class DeleteCompanyAction extends BaseCommandAction
{
    public function execute(Company $company): void
    {
        if ($company->placements()->exists() || $company->partnerships()->exists()) {
            throw new RejectedException(__('company.delete_blocked'));
        }

        $this->transaction(function () use ($company) {
            $this->log('company_deleted', $company, ['name' => $company->name]);

            $this->dispatchEvent(new CompanyDeleted($company));

            $company->delete();
        });
    }
}
