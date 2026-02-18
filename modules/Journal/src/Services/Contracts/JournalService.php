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
     * Finalizes and submits a journal entry for supervisory evaluation.
     *
     * Transitions the log from a "Draft" to a "Pending" state, certifying
     * that the student has completed the vocational tasks for the day.
     */
    public function submit(mixed $id): JournalEntry;

    /**
     * Validates and approves a journal entry by an authorized supervisor.
     *
     * Certifies the vocational evidence provided by the student, locking
     * the record to ensure historical and academic integrity.
     */
    public function approve(mixed $id, ?string $reason = null): JournalEntry;

    /**
     * Rejects a journal entry, providing pedagogical feedback for revision.
     *
     * Requires a mandatory reason to guide the student in improving the
     * quality of their vocational reflections or evidence.
     */
    public function reject(mixed $id, string $reason): JournalEntry;

    /**
     * Securely attaches digital evidence (Media) to a specific journal entry.
     *
     * Facilitates the persistence of photos or documents that serve as
     * technical proof of activity execution.
     *
     * @param array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> $files
     */
    public function attachMedia(mixed $id, array $files): void;

    /**
     * Aggregates the volume of journal entries for a specific registration.
     */
    public function getJournalCount(string $registrationId, ?string $status = null): int;

    /**
     * Calculates engagement telemetry for a specific set of student cohorts.
     *
     * Synthesizes data points to determine the "responsiveness" of students
     * in fulfilling their logging mandates.
     *
     * @param array<string> $registrationIds Authoritative UUIDs.
     *
     * @return array<string, array{submitted: int, approved: int, responsiveness: float}> Map of registration ID to stats.
     */
    public function getEngagementStats(array $registrationIds): array;
}
