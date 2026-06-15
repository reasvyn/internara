<?php

declare(strict_types=1);

namespace App\Program\InternshipGroup\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Program\InternshipGroup\Models\InternshipGroup;
use App\Program\InternshipGroup\Models\InternshipGroupMember;

final class AddMemberToGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group, array $data): InternshipGroupMember
    {
        return $this->transaction(function () use ($group, $data) {
            $member = $group->members()->create([
                'registration_id' => $data['registration_id'] ?? null,
                'mentor_id' => $data['mentor_id'] ?? null,
                'role' => $data['role'],
                'joined_at' => now(),
            ]);

            $this->log('internship_group_member_added', $member, [
                'group_id' => $group->id,
                'role' => $data['role'],
            ]);

            return $member;
        });
    }
}
