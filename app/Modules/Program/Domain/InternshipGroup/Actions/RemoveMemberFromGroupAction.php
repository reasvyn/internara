<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;

final class RemoveMemberFromGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroupMember $member): void
    {
        $this->transaction(function () use ($member) {
            $this->log('internship_group_member_removed', $member, [
                'group_id' => $member->internship_group_id,
                'role' => $member->role,
            ]);

            $member->delete();
        });
    }
}
