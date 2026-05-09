<?php

declare(strict_types=1);

namespace App\Actions\Mentee;

use App\Actions\Core\LogAuditAction;
use App\Models\Mentee;
use Illuminate\Support\Facades\DB;

class UpdateMenteeAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Mentee $mentee, array $menteeData): Mentee
    {
        return DB::transaction(function () use ($mentee, $menteeData) {
            $mentee->update($menteeData);

            $this->logAudit->execute(
                action: 'mentee_updated',
                subjectType: Mentee::class,
                subjectId: $mentee->id,
                payload: [
                    'user_id' => $mentee->user_id,
                ],
                module: 'Mentee',
            );

            return $mentee;
        });
    }
}
