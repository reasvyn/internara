<?php

declare(strict_types=1);

namespace App\Actions\Logbook;

use App\Actions\Core\LogAuditAction;
use App\Models\Logbook;
use Illuminate\Support\Facades\DB;

class DeleteLogbookAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Logbook $entry): void
    {
        DB::transaction(function () use ($entry) {
            $entryId = $entry->id;
            $userId = $entry->user_id;
            $date = $entry->date?->toDateString();

            $this->logAudit->execute(
                action: 'logbook_entry_deleted',
                subjectType: Logbook::class,
                subjectId: $entryId,
                payload: [
                    'user_id' => $userId,
                    'date' => $date,
                ],
                module: 'Logbook',
            );

            $entry->delete();
        });
    }
}
