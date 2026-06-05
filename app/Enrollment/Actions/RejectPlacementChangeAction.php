<?php

declare(strict_types=1);

namespace App\Enrollment\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Enrollment\Models\PlacementChangeRequest;

final class RejectPlacementChangeAction extends BaseAction
{
    public function execute(PlacementChangeRequest $request, string $reason): void
    {
        if ($request->status->isTerminal()) {
            throw new RejectedException('This request has already been processed.');
        }

        $this->transaction(function () use ($request, $reason) {
            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $this->log('placement_change_rejected', $request, ['reason' => $reason]);
        });
    }
}
