<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Logbook\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Journals\Domain\Logbook\Enums\LogbookStatus;
use App\Modules\Journals\Domain\Logbook\Models\Logbook;
use App\Modules\User\Models\User;
use Carbon\Carbon;

final class SubmitLogbookAction extends BaseCommandAction
{
    public function execute(User $user, array $data): Logbook
    {
        return $this->transaction(function () use ($user, $data) {
            $date = Carbon::now()->toDateString();

            $registration = $user->getActiveRegistration();

            if (! $registration) {
                throw new RejectedException(__('journals.no_active_registration'));
            }

            $existing = Logbook::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->where('status', LogbookStatus::SUBMITTED->value)
                ->first();

            if ($existing) {
                throw new RejectedException(__('logbook.already_submitted_today'));
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
                    'status' => LogbookStatus::SUBMITTED->value,
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
