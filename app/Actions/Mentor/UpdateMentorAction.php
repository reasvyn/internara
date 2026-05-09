<?php

declare(strict_types=1);

namespace App\Actions\Mentor;

use App\Actions\Core\LogAuditAction;
use App\Models\Mentor;
use Illuminate\Support\Facades\DB;

class UpdateMentorAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Mentor $mentor, array $mentorData, ?string $role = null): Mentor
    {
        return DB::transaction(function () use ($mentor, $mentorData, $role) {
            $mentor->update($mentorData);

            if ($role !== null) {
                $mentor->user->syncRoles([$role]);
            }

            $this->logAudit->execute(
                action: 'mentor_updated',
                subjectType: Mentor::class,
                subjectId: $mentor->id,
                payload: [
                    'user_id' => $mentor->user_id,
                    'type' => $mentor->type,
                    'role' => $role,
                ],
                module: 'Mentor',
            );

            return $mentor;
        });
    }
}
