<?php

declare(strict_types=1);

namespace App\Actions\Journal;

use App\Actions\Audit\LogAuditAction;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubmitJournalEntryAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): JournalEntry
    {
        return DB::transaction(function () use ($user, $data) {
            $date = Carbon::now()->toDateString();

            // Find active registration (using Spatie HasStatuses)
            $registration = $user->registrations()
                ->get()
                ->first(fn ($reg) => $reg->hasStatus('active'));

            if (! $registration) {
                throw new RuntimeException('No active internship registration found.');
            }

            // Check if a submitted journal already exists for today
            $existing = JournalEntry::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->where('status', 'submitted')
                ->first();

            if ($existing) {
                throw new RuntimeException('Journal entry for today has already been submitted.');
            }

            // Update existing draft or create new
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
