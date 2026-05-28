<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\InternshipGroupMember;

final class RemoveMemberFromGroupAction extends BaseAction
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
