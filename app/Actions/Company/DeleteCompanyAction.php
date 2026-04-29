<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipCompany;
use Illuminate\Support\Facades\DB;

class DeleteCompanyAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(InternshipCompany $company): void
    {
        DB::transaction(function () use ($company) {
            $companyId = $company->id;
            $companyName = $company->name;

            $company->delete();

            $this->logAudit->execute(
                action: 'company_deleted',
                subjectType: InternshipCompany::class,
                subjectId: $companyId,
                payload: ['name' => $companyName],
                module: 'Company'
            );
        });
    }
}
