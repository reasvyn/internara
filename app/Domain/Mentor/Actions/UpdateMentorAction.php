<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Mentor\Models\Mentor;

class UpdateMentorAction extends BaseAction
{
    public function execute(Mentor $mentor, array $mentorData, ?string $role = null): Mentor
    {
        return $this->transaction(function () use ($mentor, $mentorData, $role) {
            $mentor->update($mentorData);

            if ($role !== null) {
                $mentor->user->syncRoles([$role]);
            }

            $this->log('mentor_updated', $mentor, [
                'user_id' => $mentor->user_id,
                'type' => $mentor->type,
                'role' => $role,
            ]);

            return $mentor;
        });
    }
}
