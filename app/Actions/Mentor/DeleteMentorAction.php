<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Actions\Core\LogAuditAction;
use App\Models\Mentor;
use Illuminate\Support\Facades\DB;

class DeleteMentorAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Mentor $mentor): void
    {
        DB::transaction(function () use ($mentor) {
            $mentorId = $mentor->id;
            $userId = $mentor->user_id;
            $userEmail = $mentor->user->email;
            $mentorType = $mentor->type;

            $this->logAudit->execute(
                action: 'mentor_deleted',
                subjectType: Mentor::class,
                subjectId: $mentorId,
                payload: [
                    'user_id' => $userId,
                    'email' => $userEmail,
                    'type' => $mentorType,
                ],
                module: 'Mentor',
            );

            $mentor->user->delete();
        });
    }
}
