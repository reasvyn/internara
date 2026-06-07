<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;
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

            if (! empty($data['photos'])) {
                foreach ($data['photos'] as $photo) {
                    $journal->addMedia($photo)->toMediaCollection('photos');
                }
            }

            $this->log('journal_submitted', $journal, ['date' => $journal->date]);

            return $journal;
        });
    }
}
