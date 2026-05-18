<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Entities\Placement\PlacementCapacity;
use App\Models\Placement;
use App\Models\PlacementChangeRequest;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApprovePlacementChangeAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(PlacementChangeRequest $request): void
    {
        if ($request->status->isTerminal()) {
            throw new RuntimeException('This request has already been processed.');
        }

        DB::transaction(function () use ($request) {
            $registration = Registration::findOrFail($request->registration_id);
            $oldPlacement = Placement::findOrFail($request->from_placement_id);
            $newPlacement = Placement::findOrFail($request->to_placement_id);

            if (! PlacementCapacity::fromModel($newPlacement)->hasAvailableSlots()) {
                throw new RuntimeException('Target placement is full.');
            }

            $oldPlacement->decrement('filled_quota');
            $newPlacement->increment('filled_quota');

            $registration->update([
                'placement_id' => $newPlacement->id,
                'start_date' => $newPlacement->start_date ?? $registration->start_date,
                'end_date' => $newPlacement->end_date ?? $registration->end_date,
            ]);

            $request->update([
                'status' => 'approved',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $this->logAudit->execute(
                action: 'placement_change_approved',
                subjectType: PlacementChangeRequest::class,
                subjectId: $request->id,
                payload: ['registration_id' => $registration->id],
                module: 'PlacementChange',
            );
        });
    }
}
