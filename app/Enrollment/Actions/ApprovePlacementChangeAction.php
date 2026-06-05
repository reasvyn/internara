<?php

declare(strict_types=1);

namespace App\Enrollment\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Entities\PlacementCapacity;
use App\Enrollment\Models\Placement;
use App\Enrollment\Models\PlacementChangeRequest;
use App\Enrollment\Models\Registration;

final class ApprovePlacementChangeAction extends BaseAction
{
    public function execute(PlacementChangeRequest $request): void
    {
        if ($request->status->isTerminal()) {
            throw new RejectedException('This request has already been processed.');
        }

        $this->transaction(function () use ($request) {
            $registration = Registration::findOrFail($request->registration_id);
            $oldPlacement = Placement::findOrFail($request->from_placement_id);
            $newPlacement = Placement::findOrFail($request->to_placement_id);

            if (! PlacementCapacity::fromModel($newPlacement)->hasAvailableSlots()) {
                throw new RejectedException('Target placement is full.');
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

            $this->log('placement_change_approved', $request, ['registration_id' => $registration->id]);
        });
    }
}
