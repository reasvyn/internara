<?php

declare(strict_types=1);

namespace Modules\Journal\Services\Contracts;

use Modules\Journal\Models\JournalEntry;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Interface JournalService
 *
 * Handles the business logic for student Daily Journals.
 *
 * @extends EloquentQuery<JournalEntry>
 */
interface JournalService extends EloquentQuery
{
    /**
     * Submit a journal entry for review.
     */
    public function submit(mixed $id): JournalEntry;

    /**
     * Approve a journal entry by a supervisor.
     */
    public function approve(mixed $id, ?string $reason = null): JournalEntry;

    /**
     * Reject a journal entry with a reason.
     */
    public function reject(mixed $id, string $reason): JournalEntry;

    /**
     * Attach media files to a journal entry.
     *
     * @param array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> $files
     */
    public function attachMedia(mixed $id, array $files): void;

    /**
     * Get the total count of journal entries for a specific registration.
     */
    public function getJournalCount(string $registrationId, ?string $status = null): int;
}
