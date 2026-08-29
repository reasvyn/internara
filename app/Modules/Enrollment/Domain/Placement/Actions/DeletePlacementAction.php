<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;

final class DeletePlacementAction extends BaseCommandAction
{
    public function execute(Placement $placement): void
    {
        $placement->loadCount('registrations');

        if (! $placement->asPlacementState()->canBeDeleted()) {
            throw new RejectedException(__('placement.has_active_registrations'));
        }

        $this->transaction(function () use ($placement) {
            $this->log('placement_deleted', $placement, ['name' => $placement->name]);

            $placement->delete();
        });
    }
}
