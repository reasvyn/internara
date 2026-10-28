<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Data\RelocateStudentData;
use App\Modules\Enrollment\Domain\Placement\Entities\PlacementCapacity;
use App\Modules\Enrollment\Domain\Placement\Enums\PlacementChangeStatus;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

final class RelocateStudentAction extends BaseCommandAction
{
    /**
     * Relocate an active student to an available target placement based on an offline request.
     */
    public function execute(RelocateStudentData $data): PlacementChangeRequest
    {
        $registration = Registration::findOrFail($data->registrationId);

        if (! $registration->placement_id) {
            throw new RejectedException(__('placement_change.no_current_placement'));
        }

        $oldPlacement = Placement::findOrFail($registration->placement_id);
        $targetPlacement = Placement::findOrFail($data->targetPlacementId);

        if ($targetPlacement->id === $oldPlacement->id) {
            throw new RejectedException(__('placement_change.same_placement'));
        }

        if ($targetPlacement->internship_id !== $registration->internship_id) {
            throw new RejectedException(__('placement_change.different_internship'));
        }

        if (! PlacementCapacity::fromModel($targetPlacement)->hasAvailableSlots()) {
            throw new RejectedException(__('placement.target_full'));
        }

        return $this->transaction(function () use (
            $registration,
            $oldPlacement,
            $targetPlacement,
            $data
        ) {
            $oldPlacement->decrement('filled_quota');
            $targetPlacement->increment('filled_quota');

            $registration->update([
                'placement_id' => $targetPlacement->id,
                'start_date' => $targetPlacement->start_date ?? $registration->start_date,
                'end_date' => $targetPlacement->end_date ?? $registration->end_date,
            ]);

            /** @var PlacementChangeRequest $changeRecord */
            $changeRecord = PlacementChangeRequest::create([
                'registration_id' => $registration->id,
                'from_placement_id' => $oldPlacement->id,
                'to_placement_id' => $targetPlacement->id,
                'reason' => $data->reason,
                'requested_by' => $registration->student_id,
                'status' => PlacementChangeStatus::APPROVED->value,
                'processed_by' => $data->adminId,
                'processed_at' => now(),
            ]);

            $this->log('placement_student_relocated', $changeRecord, [
                'registration_id' => $registration->id,
                'from_placement_id' => $oldPlacement->id,
                'to_placement_id' => $targetPlacement->id,
                'reason' => $data->reason,
                'processed_by' => $data->adminId,
            ]);

            return $changeRecord;
        });
    }
}
