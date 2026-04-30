<?php

declare(strict_types=1);

namespace App\Actions\Journal;

use App\Actions\Audit\LogAuditAction;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubmitJournalEntryAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): JournalEntry
    {
        return DB::transaction(function () use ($user, $data) {
            $date = $data['date'] ?? Carbon::now()->toDateString();
            
            // Find active registration
            $registration = $user->registrations()
                ->where('status', 'active')
                ->first();

            if (!$registration) {
                throw new \Exception('No active internship registration found.');
            }

            $journal = JournalEntry::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $date,
                ],
                [
                    'registration_id' => $registration->id,
                    'content' => $data['content'],
                    'learning_outcomes' => $data['learning_outcomes'] ?? null,
                    'status' => 'submitted',
                ]
            );

            $this->logAudit->execute(
                action: 'journal_submitted',
                subjectType: JournalEntry::class,
                subjectId: $journal->id,
                payload: ['date' => $journal->date],
                module: 'Journal'
            );

            return $journal;
        });
    }
}
