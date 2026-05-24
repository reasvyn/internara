<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\InternshipGroup;

class UpdateInternshipGroupAction extends BaseAction
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
