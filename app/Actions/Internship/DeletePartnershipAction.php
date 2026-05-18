<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Partnership;
use Illuminate\Support\Facades\DB;

class DeletePartnershipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Partnership $partnership): void
    {
        DB::transaction(function () use ($partnership) {
            $this->logAudit->execute(
                action: 'partnership_deleted',
                subjectType: Partnership::class,
                subjectId: $partnership->id,
                payload: ['agreement_number' => $partnership->agreement_number],
                module: 'Partnership',
            );

            $partnership->delete();
        });
    }
}
