<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;

final class UpdateInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group, array $data): InternshipGroup
    {
        return $this->transaction(function () use ($group, $data) {
            $group->update($data);

            $this->log('internship_group_updated', $group, $data);

            return $group;
        });
    }
}
