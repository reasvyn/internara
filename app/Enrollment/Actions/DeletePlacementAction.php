<?php

declare(strict_types=1);

namespace App\Enrollment\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Enrollment\Models\Placement;

final class DeletePlacementAction extends BaseAction
{
    public function execute(Placement $placement): void
    {
        if (! $placement->asPlacementState()->canBeDeleted()) {
            throw new RejectedException('Cannot delete placement with active registrations.');
        }

        $this->transaction(function () use ($placement) {
            $this->log('placement_deleted', $placement, ['name' => $placement->name]);

            $placement->delete();
        });
    }
}
