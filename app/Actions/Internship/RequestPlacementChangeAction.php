<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Placement;
use App\Models\PlacementChangeRequest;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class RequestPlacementChangeAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Registration $registration, array $data): PlacementChangeRequest
    {
        $validated = Validator::validate($data, [
            'to_placement_id' => 'required|exists:internship_placements,id',
            'reason' => 'required|string|max:2000',
            'requested_by' => 'required|exists:users,id',
        ]);

        return DB::transaction(function () use ($registration, $validated) {
            $exists = PlacementChangeRequest::where('registration_id', $registration->id)
                ->where('status', 'pending')
                ->exists();

            if ($exists) {
                throw new RuntimeException('A pending change request already exists for this registration.');
            }

            $targetPlacement = Placement::findOrFail($validated['to_placement_id']);

            $request = PlacementChangeRequest::create([
                'registration_id' => $registration->id,
                'from_placement_id' => $registration->placement_id,
                'to_placement_id' => $targetPlacement->id,
                'reason' => $validated['reason'],
                'requested_by' => $validated['requested_by'],
            ]);

            $this->logAudit->execute(
                action: 'placement_change_requested',
                subjectType: PlacementChangeRequest::class,
                subjectId: $request->id,
                payload: ['from_placement' => $registration->placement_id, 'to_placement' => $targetPlacement->id],
                module: 'PlacementChange',
            );

            return $request;
        });
    }
}
