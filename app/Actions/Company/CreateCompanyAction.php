<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Actions\Audit\LogAuditAction;
use App\Models\InternshipCompany;
use Illuminate\Support\Facades\DB;

class CreateCompanyAction
{
    public function __construct(protected LogAuditAction $logAudit) {}

    public function execute(array $data): InternshipCompany
    {
        return DB::transaction(function () use ($data) {
            $company = InternshipCompany::create($data);

            $this->logAudit->execute(
                action: 'company_created',
                subjectType: InternshipCompany::class,
                subjectId: $company->id,
                payload: ['name' => $company->name],
                module: 'Company'
            );

            return $company;
        });
    }
}
