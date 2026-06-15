<?php

declare(strict_types=1);

namespace App\Program\InternshipGroup\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Program\InternshipGroup\Models\InternshipGroup;

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
