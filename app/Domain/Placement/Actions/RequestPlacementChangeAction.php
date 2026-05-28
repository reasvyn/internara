<?php

declare(strict_types=1);

namespace App\Domain\Placement\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Placement\Models\Placement;
use App\Domain\Placement\Models\PlacementChangeRequest;
use App\Domain\Registration\Models\Registration;
use Illuminate\Support\Facades\Validator;

final class RequestPlacementChangeAction extends BaseAction
{
    public function execute(Registration $registration, array $data): PlacementChangeRequest
    {
        $validated = Validator::validate($data, [
            'to_placement_id' => 'required|exists:placements,id',
            'reason' => 'required|string|max:2000',
            'requested_by' => 'required|exists:users,id',
        ]);

        return $this->transaction(function () use ($registration, $validated) {
            $exists = PlacementChangeRequest::where('registration_id', $registration->id)
                ->where('status', 'pending')
                ->exists();

            if ($exists) {
                throw new RejectedException('A pending change request already exists for this registration.');
            }

            $targetPlacement = Placement::findOrFail($validated['to_placement_id']);

            $request = PlacementChangeRequest::create([
                'registration_id' => $registration->id,
                'from_placement_id' => $registration->placement_id,
                'to_placement_id' => $targetPlacement->id,
                'reason' => $validated['reason'],
                'requested_by' => $validated['requested_by'],
            ]);

            $this->log('placement_change_requested', $request, ['from_placement' => $registration->placement_id, 'to_placement' => $targetPlacement->id]);

            return $request;
        });
    }
}
