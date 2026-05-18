<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\PlacementChangeRequest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RejectPlacementChangeAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(PlacementChangeRequest $request, string $reason): void
    {
        if ($request->status->isTerminal()) {
            throw new RuntimeException('This request has already been processed.');
        }

        DB::transaction(function () use ($request, $reason) {
            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $this->logAudit->execute(
                action: 'placement_change_rejected',
                subjectType: PlacementChangeRequest::class,
                subjectId: $request->id,
                payload: ['reason' => $reason],
                module: 'PlacementChange',
            );
        });
    }
}
