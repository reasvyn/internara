<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Enrollment\Models\Placement;

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
