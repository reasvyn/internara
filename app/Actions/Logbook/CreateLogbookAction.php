<?php

declare(strict_types=1);

namespace App\Actions\Logbook;

use App\Actions\Core\LogAuditAction;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateLogbookAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): Logbook
    {
        return DB::transaction(function () use ($user, $data) {
            $entry = Logbook::create([
                'user_id' => $user->id,
                'registration_id' => $data['registration_id'],
                'date' => $data['date'],
                'content' => $data['content'],
                'learning_outcomes' => $data['learning_outcomes'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'is_verified' => $data['is_verified'] ?? false,
                'verified_by' => isset($data['is_verified']) && $data['is_verified'] ? auth()->id() : null,
                'verified_at' => isset($data['is_verified']) && $data['is_verified'] ? now() : null,
            ]);

            $this->logAudit->execute(
                action: 'logbook_entry_created',
                subjectType: Logbook::class,
                subjectId: $entry->id,
                payload: [
                    'user_id' => $user->id,
                    'date' => $entry->date->toDateString(),
                    'status' => $entry->status->value,
                ],
                module: 'Logbook',
            );

            return $entry;
        });
    }
}
