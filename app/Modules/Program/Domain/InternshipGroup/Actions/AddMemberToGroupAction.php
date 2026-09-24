<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;

final class AddMemberToGroupAction extends BaseCommandAction
{
    public function execute(InternshipGroup $group, array $data): InternshipGroupMember
    {
        return $this->transaction(function () use ($group, $data) {
            $registrationId = ! empty($data['registration_id']) ? $data['registration_id'] : null;
            $userId = ! empty($data['user_id']) ? $data['user_id'] : (! empty($data['mentor_id']) ? $data['mentor_id'] : null);

            $member = $group->members()->create([
                'registration_id' => $registrationId,
                'user_id' => $userId,
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
