<?php

declare(strict_types=1);

namespace App\Actions\Logbook;

use App\Actions\Core\LogAuditAction;
use App\Models\Briefing;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateLogbookAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(string $userId, array $data): Logbook
    {
        return DB::transaction(function () use ($userId, $data) {
            $user = User::findOrFail($userId);
            $registration = $user->getActiveRegistration();

            if (! $registration) {
                throw new RuntimeException('No active internship registration found.');
            }

            if (! Briefing::hasStudentCompletedMandatoryBriefing($user->id, $registration->internship_id)) {
                throw new RuntimeException('You must attend the mandatory briefing before submitting logbook entries.');
            }

            $entry = Logbook::create([
                'user_id' => $userId,
                'registration_id' => $registration->id,
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
                    'user_id' => $userId,
                    'date' => $entry->date->toDateString(),
                    'status' => $entry->status->value,
                ],
                module: 'Logbook',
            );

            return $entry;
        });
    }
}
