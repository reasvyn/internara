<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Partnership;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TerminatePartnershipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Partnership $partnership): Partnership
    {
        if (! $partnership->asPartnershipState()->isActive()) {
            throw new RuntimeException('Only active partnerships can be terminated.');
        }

        return DB::transaction(function () use ($partnership) {
            $partnership->update(['status' => 'terminated']);

            $this->logAudit->execute(
                action: 'partnership_terminated',
                subjectType: Partnership::class,
                subjectId: $partnership->id,
                payload: ['agreement_number' => $partnership->agreement_number],
                module: 'Partnership',
            );

            return $partnership->fresh();
        });
    }
}
