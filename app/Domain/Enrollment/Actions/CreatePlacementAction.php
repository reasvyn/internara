<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Enrollment\Models\Placement;

final class CreatePlacementAction extends BaseAction
{
    public function execute(array $data): Placement
    {
        return $this->transaction(function () use ($data) {
            $data['filled_quota'] = 0;
            $placement = Placement::create($data);

            $this->log('placement_created', $placement, ['name' => $placement->name, 'quota' => $placement->quota]);

            return $placement;
        });
    }
}
