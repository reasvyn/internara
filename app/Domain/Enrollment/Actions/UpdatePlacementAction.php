<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Enrollment\Models\Placement;

final class UpdatePlacementAction extends BaseAction
{
    public function execute(Placement $placement, array $data): Placement
    {
        return $this->transaction(function () use ($placement, $data) {
            $placement->update($data);

            $this->log('placement_updated', $placement, ['name' => $placement->name, 'quota' => $placement->quota]);

            return $placement;
        });
    }
}
