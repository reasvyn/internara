<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Entities\PlacementCapacity;
use App\Modules\Enrollment\Domain\Placement\Enums\PlacementChangeStatus;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

final class ApprovePlacementChangeAction extends BaseCommandAction
{
    public function execute(PlacementChangeRequest $request): void
    {
        if ($request->status->isTerminal()) {
            throw new RejectedException(__('placement.already_processed'));
        }

        $this->transaction(function () use ($request) {
            $registration = Registration::findOrFail($request->registration_id);
            $oldPlacement = Placement::findOrFail($request->from_placement_id);
            $newPlacement = Placement::findOrFail($request->to_placement_id);

            if (! PlacementCapacity::fromModel($newPlacement)->hasAvailableSlots()) {
                throw new RejectedException(__('placement.target_full'));
            }

            $oldPlacement->decrement('filled_quota');
            $newPlacement->increment('filled_quota');

            $registration->update([
                'placement_id' => $newPlacement->id,
                'start_date' => $newPlacement->start_date ?? $registration->start_date,
                'end_date' => $newPlacement->end_date ?? $registration->end_date,
            ]);

            $request->update([
                'status' => PlacementChangeStatus::APPROVED->value,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $this->log('placement_change_approved', $request, [
                'registration_id' => $registration->id,
            ]);
        });
    }
}
