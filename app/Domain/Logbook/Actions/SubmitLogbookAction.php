<?php

declare(strict_types=1);

namespace App\Domain\Logbook\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Logbook\Models\Logbook;
use App\Domain\User\Models\User;
use Carbon\Carbon;

final class SubmitLogbookAction extends BaseAction
{
    public function execute(User $user, array $data): Logbook
    {
        return $this->transaction(function () use ($user, $data) {
            $date = Carbon::now()->toDateString();

            $registration = $user->getActiveRegistration();

            if (! $registration) {
                throw new RejectedException('No active internship registration found.');
            }

            $existing = Logbook::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->where('status', 'submitted')
                ->first();

            if ($existing) {
                throw new RejectedException('Journal entry for today has already been submitted.');
            }

            $journal = Logbook::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $date,
                ],
                [
                    'registration_id' => $registration->id,
                    'content' => $data['content'],
                    'learning_outcomes' => $data['learning_outcomes'] ?? null,
                    'status' => 'submitted',
                ],
            );

            $this->log('journal_submitted', $journal, ['date' => $journal->date]);

            return $journal;
        });
    }
}
