<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\InternshipGroup\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Program\Aggregates\Internship\Models\InternshipGroup;

final class CreateInternshipGroupAction extends BaseAction
{
    public function execute(array $data): InternshipGroup
    {
        return $this->transaction(function () use ($data) {
            $group = InternshipGroup::create($data);

            $this->log('internship_group_created', $group, $data);

            return $group;
        });
    }
}
