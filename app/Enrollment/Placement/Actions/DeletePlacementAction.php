<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Placement;

final class DeletePlacementAction extends BaseAction
{
    public function execute(Placement $placement): void
    {
        if (! $placement->asPlacementState()->canBeDeleted()) {
            throw new RejectedException(__('placement.has_active_registrations'));
        }

        $this->transaction(function () use ($placement) {
            $this->log('placement_deleted', $placement, ['name' => $placement->name]);

            $placement->delete();
        });
    }
}
