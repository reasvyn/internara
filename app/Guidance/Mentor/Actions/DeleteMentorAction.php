<?php

declare(strict_types=1);

namespace App\Guidance\Mentor\Actions;

use App\Core\Actions\BaseAction;
use App\Guidance\Mentor\Models\Mentor;

final class DeleteMentorAction extends BaseAction
{
    public function execute(Mentor $mentor): void
    {
        $this->transaction(function () use ($mentor) {
            $this->log('mentor_deleted', $mentor, [
                'user_id' => $mentor->user_id,
                'email' => $mentor->user->email,
                'type' => $mentor->type,
            ]);

            $mentor->user->delete();
        });
    }
}
