<?php

declare(strict_types=1);

namespace Modules\Journal\Services;

use Modules\Assessment\Services\Contracts\CompetencyService;
use Modules\Exception\AppException;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Models\JournalEntry;
use Modules\Journal\Services\Contracts\JournalService as Contract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class JournalService
 *
 * Implementation for managing student daily journals.
 */
class JournalService extends EloquentQuery implements Contract
{
    /**
     * JournalService constructor.
     */
    public function __construct(
        protected RegistrationService $registrationService,
        protected CompetencyService $competencyService,
        JournalEntry $model,
    ) {
        $this->setModel($model);
        $this->setSearchable(['work_topic', 'activity_description']);
        $this->setSortable(['date', 'created_at']);
    }

    /**
     * {@inheritDoc}
     */
    protected function applyFilters(&$query, array &$filters): void
    {
        if (isset($filters['date'])) {
            $query->whereDate('date', $filters['date']);
            unset($filters['date']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
            unset($filters['start_date'], $filters['end_date']);
        }

        parent::applyFilters($query, $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): JournalEntry
    {
        \Illuminate\Support\Facades\Gate::authorize('create', JournalEntry::class);

        // Gating Invariant: Briefing/Guidance must be completed if enabled
        $registrationId = $data['registration_id'];
        $registration = $this->registrationService->find($registrationId);

        if (! $registration) {
            throw new AppException(
                userMessage: 'internship::exceptions.registration_not_found',
                code: 404,
            );
        }

        $settingService = app(\Modules\Setting\Services\Contracts\SettingService::class);
        $guidanceService = app(\Modules\Guidance\Services\Contracts\HandbookService::class);

        if (
            $settingService->getValue('feature_guidance_enabled', true) &&
            ! $guidanceService->hasCompletedMandatory($registration->student_id)
        ) {
            throw new AppException(
                userMessage: 'guidance::messages.must_complete_guidance',
                code: 403,
            );
        }

        // Period Invariant: activities are restricted to assigned date range
        $journalDate = $data['date'] ?? now()->format('Y-m-d');
        if (
            ($registration->start_date &&
                $journalDate < $registration->start_date->format('Y-m-d')) ||
            ($registration->end_date && $journalDate > $registration->end_date->format('Y-m-d'))
        ) {
            throw new AppException(
                userMessage: 'journal::exceptions.outside_internship_period',
                code: 403,
            );
        }

        // Submission Window Invariant: journals must be submitted within the defined window
        $window = (int) setting('journal_submission_window', 7);
        $diff = now()->diffInDays(\Illuminate\Support\Carbon::parse($journalDate), false);

        if (abs($diff) > $window && $diff < 0) {
            throw new AppException(
                userMessage: 'journal::exceptions.submission_window_expired',
                replace: ['days' => $window],
                code: 403,
            );
        }

        $competencyIds = $data['competency_ids'] ?? [];
        unset($data['competency_ids']);

        /** @var JournalEntry $entry */
        $entry = parent::create($data);

        if (! empty($competencyIds)) {
            $this->competencyService->syncJournalCompetencies($entry->id, $competencyIds);
        }

        return $entry;
    }

    /**
     * {@inheritDoc}
     */
    public function update(mixed $id, array $data): JournalEntry
    {
        $entry = $this->find($id);

        // Constraint: Journal cannot be modified after it is approved or verified
        if (in_array($entry->latestStatus()?->name, ['approved', 'verified'])) {
            throw new AppException(
                userMessage: 'journal::exceptions.cannot_edit_locked_journal',
                code: 403,
            );
        }

        // Submission Window Invariant: journals must be updated within the defined window
        $window = (int) setting('journal_submission_window', 7);
        $diff = now()->diffInDays($entry->date, false);

        if (abs($diff) > $window && $diff < 0) {
            throw new AppException(
                userMessage: 'journal::exceptions.submission_window_expired',
                replace: ['days' => $window],
                code: 403,
            );
        }

        $competencyIds = $data['competency_ids'] ?? null;
        unset($data['competency_ids']);

        /** @var JournalEntry $updatedEntry */
        $updatedEntry = parent::update($id, $data);

        if ($competencyIds !== null) {
            $this->competencyService->syncJournalCompetencies($updatedEntry->id, $competencyIds);
        }

        return $updatedEntry;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(mixed $id, bool $force = false): bool
    {
        $entry = $this->find($id);

        // Only drafts can be deleted
        if ($entry->latestStatus()?->name !== 'draft') {
            throw new AppException(
                userMessage: 'journal::exceptions.only_drafts_can_be_deleted',
                code: 403,
            );
        }

        return parent::delete($id, $force);
    }

    /**
     * {@inheritDoc}
     */
    public function submit(mixed $id): JournalEntry
    {
        $entry = $this->find($id);
        $entry->setStatus('submitted', 'Journal submitted by student.');

        return $entry;
    }

    /**
     * {@inheritDoc}
     */
    public function approve(mixed $id, ?string $reason = null): JournalEntry
    {
        $entry = $this->find($id);

        // Authorization is handled by JournalPolicy@validate
        // This accepts both Teachers and Mentors as valid authorizers
        $entry->setStatus('approved', $reason ?? 'Journal approved by authorized supervisor.');

        return $entry;
    }

    /**
     * {@inheritDoc}
     */
    public function reject(mixed $id, string $reason): JournalEntry
    {
        $entry = $this->find($id);
        $entry->setStatus('rejected', $reason);

        return $entry;
    }

    /**
     * {@inheritdoc}
     */
    public function attachMedia(mixed $id, array $files): void
    {
        $entry = $this->find($id);

        foreach ($files as $file) {
            $entry->addMedia($file)->toMediaCollection('attachments');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getJournalCount(string $registrationId, ?string $status = null): int
    {
        $query = $this->model->newQuery()->where('registration_id', $registrationId);

        if ($status) {
            $query->currentStatus($status);
        }

        return $query->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getEngagementStats(array $registrationIds): array
    {
        if (empty($registrationIds)) {
            return [];
        }

        // Fetch counts in a single query grouped by registration_id
        $results = $this->model
            ->newQuery()
            ->select('registration_id')
            ->selectRaw("COUNT(CASE WHEN EXISTS (
                SELECT 1 FROM statuses 
                WHERE statuses.model_id = journal_entries.id 
                AND statuses.model_type = ? 
                AND name IN ('submitted', 'approved', 'verified')
            ) THEN 1 END) as submitted_count", [$this->model->getMorphClass()])
            ->selectRaw("COUNT(CASE WHEN EXISTS (
                SELECT 1 FROM statuses 
                WHERE statuses.model_id = journal_entries.id 
                AND statuses.model_type = ? 
                AND name IN ('approved', 'verified')
            ) THEN 1 END) as approved_count", [$this->model->getMorphClass()])
            ->whereIn('registration_id', $registrationIds)
            ->groupBy('registration_id')
            ->get()
            ->keyBy('registration_id');

        $statsMap = [];
        foreach ($registrationIds as $id) {
            $row = $results->get($id);
            $submitted = (int) ($row->submitted_count ?? 0);
            $approved = (int) ($row->approved_count ?? 0);
            $responsiveness = $submitted > 0 ? ($approved / $submitted) * 100 : 0.0;

            $statsMap[$id] = [
                'submitted' => $submitted,
                'approved' => $approved,
                'responsiveness' => round($responsiveness, 2),
            ];
        }

        return $statsMap;
    }
}
