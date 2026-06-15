<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Enrollment\Placement;

final class UpdatePlacementAction extends BaseCommandAction
{
    public function execute(Placement $placement, array $data): Placement
    {
        return $this->transaction(function () use ($placement, $data) {
            $placement->update($data);

            $this->log('placement_updated', $placement, [
                'name' => $placement->name,
                'quota' => $placement->quota,
            ]);

            return $placement;
        });
    }
}
