<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Actions;

use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;

final class AddMembersToGroupAction extends BaseProcessAction
{
    public function __construct(protected readonly AddMemberToGroupAction $addMember) {}

    /**
     * Create every member row within a single transaction (all-or-nothing).
     *
     * @param array<int, array{role: string, registration_id?: string|null, mentor_id?: string|null}> $rows
     */
    public function execute(InternshipGroup $group, array $rows): int
    {
        return $this->transaction(function () use ($group, $rows) {
            foreach ($rows as $row) {
                $this->addMember->execute($group, $row);
            }

            $count = count($rows);

            $this->log('internship_group_members_added', $group, [
                'group_id' => $group->id,
                'count' => $count,
            ]);

            return $count;
        });
    }
}
