<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipCompany;
use Illuminate\Support\Facades\DB;

class UpdateCompanyAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(InternshipCompany $company, array $data): InternshipCompany
    {
        return DB::transaction(function () use ($company, $data) {
            $company->update($data);

            $this->logAudit->execute(
                action: 'company_updated',
                subjectType: InternshipCompany::class,
                subjectId: $company->id,
                payload: ['name' => $company->name],
                module: 'Company'
            );

            return $company;
        });
    }
}
