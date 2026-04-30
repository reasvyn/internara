<?php

declare(strict_types=1);

namespace Modules\Journal\Services\Contracts;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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
     * Updates an existing journal entry with vocational data.
     */
    public function update(JournalEntry $entry, array $data): void;

    /**
     * Removes a journal entry from the registry.
     */
    public function delete(JournalEntry $entry): bool;

    /**
     * Finalizes and submits a journal entry for supervisory evaluation.
     *
     * Transitions the log from a "Draft" to a "Pending" state, certifying
     * that the student has completed the vocational tasks for the day.
     */
    public function submit(JournalEntry $entry): JournalEntry;

    /**
     * Validates and approves a journal entry by an authorized supervisor.
     *
     * Certifies the vocational evidence provided by the student, locking
     * the record to ensure historical and academic integrity.
     */
    public function approve(JournalEntry $entry, ?string $reason = null): JournalEntry;

    /**
     * Rejects a journal entry, providing pedagogical feedback for revision.
     *
     * Requires a mandatory reason to guide the student in improving the
     * quality of their vocational reflections or evidence.
     */
    public function reject(JournalEntry $entry, string $reason): JournalEntry;

    /**
     * Confirms the vocational evidence by an Industry Mentor.
     */
    public function verifyField(JournalEntry $entry): JournalEntry;

    /**
     * Confirms the academic alignment by a Teacher.
     */
    public function verifyAcademic(JournalEntry $entry): JournalEntry;

    /**
     * Checks if a journal entry is locked for editing.
     */
    public function isLocked(JournalEntry $entry): bool;

    /**
     * Securely attaches digital evidence (Media) to a specific journal entry.
     *
     * Facilitates the persistence of photos or documents that serve as
     * technical proof of activity execution.
     *
     * @param array<TemporaryUploadedFile> $files
     */
    public function attachMedia(JournalEntry $entry, array $files): void;

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
