<?php

declare(strict_types=1);

namespace App\Actions\Logbook;

use App\Actions\Core\LogAuditAction;
use App\Models\Logbook;
use Illuminate\Support\Facades\DB;

class UpdateLogbookAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Logbook $entry, array $data): Logbook
    {
        return DB::transaction(function () use ($entry, $data) {
            $updateData = array_filter([
                'content' => $data['content'] ?? null,
                'learning_outcomes' => $data['learning_outcomes'] ?? null,
                'status' => $data['status'] ?? null,
                'is_verified' => $data['is_verified'] ?? null,
                'verified_by' => isset($data['is_verified']) && $data['is_verified'] ? auth()->id() : null,
                'verified_at' => isset($data['is_verified']) && $data['is_verified'] ? now() : null,
                'mentor_feedback' => $data['mentor_feedback'] ?? null,
            ], fn ($v) => $v !== null);

            if ($updateData !== []) {
                $entry->update($updateData);
            }

            $this->logAudit->execute(
                action: 'logbook_entry_updated',
                subjectType: Logbook::class,
                subjectId: $entry->id,
                payload: [
                    'user_id' => $entry->user_id,
                    'date' => $entry->date?->toDateString(),
                    'status' => $entry->status?->value,
                ],
                module: 'Logbook',
            );

            return $entry;
        });
    }
}
