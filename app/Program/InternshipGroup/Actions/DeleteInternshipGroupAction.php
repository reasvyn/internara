<?php

declare(strict_types=1);

namespace App\Program\InternshipGroup\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Program\InternshipGroup\Models\InternshipGroup;

final class DeleteInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group): void
    {
        if (! $group->asInternshipGroupState()->canBeDeleted()) {
            throw new RejectedException('Cannot delete a group with active members.');
        }

        $this->transaction(function () use ($group) {
            $this->log('internship_group_deleted', $group, ['name' => $group->name]);

            $group->delete();
        });
    }
}
