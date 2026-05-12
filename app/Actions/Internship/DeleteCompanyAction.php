<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeleteCompanyAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Company $company): void
    {
        if (! $company->asCompanyState()->canBeDeleted()) {
            throw new RuntimeException('Cannot delete company with existing placements.');
        }

        DB::transaction(function () use ($company) {
            $companyId = $company->id;
            $companyName = $company->name;

            $company->delete();

            $this->logAudit->execute(
                action: 'company_deleted',
                subjectType: Company::class,
                subjectId: $companyId,
                payload: ['name' => $companyName],
                module: 'Company',
            );
        });
    }
}
