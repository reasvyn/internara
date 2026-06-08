<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;

final class CreateLogbookAction extends BaseAction
{
    public function execute(string $userId, array $data): Logbook
    {
        return $this->transaction(function () use ($userId, $data) {
            $user = User::findOrFail($userId);
            $registration = $user->getActiveRegistration();

            if (! $registration) {
                throw new RejectedException('No active internship registration found.');
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

            $this->log('logbook_entry_created', $entry, [
                'user_id' => $userId,
                'date' => $entry->date->toDateString(),
                'status' => $entry->status->value,
            ]);

            return $entry;
        });
    }
}
