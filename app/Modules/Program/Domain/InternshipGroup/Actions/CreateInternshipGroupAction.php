<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;

final class CreateInternshipGroupAction extends BaseCommandAction
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
