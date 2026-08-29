<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;

final class DeleteInternshipGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group): void
    {
        if (! $group->asInternshipGroupState()->canBeDeleted()) {
            throw new RejectedException(__('internship.delete_group_blocked'));
        }

        $this->transaction(function () use ($group) {
            $this->log('internship_group_deleted', $group, ['name' => $group->name]);

            $group->delete();
        });
    }
}
