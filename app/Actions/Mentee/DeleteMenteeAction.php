<?php

declare(strict_types=1);

namespace App\Actions\Mentee;

use App\Actions\Core\LogAuditAction;
use App\Models\Mentee;
use Illuminate\Support\Facades\DB;

class DeleteMenteeAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Mentee $mentee): void
    {
        DB::transaction(function () use ($mentee) {
            $menteeId = $mentee->id;
            $userId = $mentee->user_id;
            $userEmail = $mentee->user->email;

            $this->logAudit->execute(
                action: 'mentee_deleted',
                subjectType: Mentee::class,
                subjectId: $menteeId,
                payload: [
                    'user_id' => $userId,
                    'email' => $userEmail,
                ],
                module: 'Mentee',
            );

            $mentee->user->delete();
        });
    }
}
