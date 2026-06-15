<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement\Enums\PlacementChangeStatus;
use App\Enrollment\Placement\Models\PlacementChangeRequest;

final class RejectPlacementChangeAction extends BaseCommandAction
{
    public function execute(PlacementChangeRequest $request, string $reason): void
    {
        if ($request->status->isTerminal()) {
            throw new RejectedException(__('placement.already_processed'));
        }

        $this->transaction(function () use ($request, $reason) {
            $request->update([
                'status' => PlacementChangeStatus::REJECTED->value,
                'rejection_reason' => $reason,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $this->log('placement_change_rejected', $request, ['reason' => $reason]);
        });
    }
}
