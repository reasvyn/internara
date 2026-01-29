<?php

declare(strict_types=1);

namespace Modules\Journal\Services;

use Modules\Exception\AppException;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;
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
        protected InternshipRegistrationService $registrationService,
        JournalEntry $model,
    ) {
        $this->setModel($model);
        $this->setSearchable(['work_topic', 'activity_description', 'basic_competence']);
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
        $registrationId = $data['registration_id'];
        $registration = $this->registrationService->find($registrationId);

        if (! $registration) {
            throw new AppException(
                userMessage: 'internship::exceptions.registration_not_found',
                code: 404,
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

        return parent::create($data);
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

        return parent::update($id, $data);
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
            return ['submitted' => 0, 'approved' => 0, 'responsiveness' => 0.0];
        }

        $submitted = $this->model
            ->newQuery()
            ->whereIn('registration_id', $registrationIds)
            ->whereHas(
                'statuses',
                fn ($q) => $q->whereIn('name', ['submitted', 'approved', 'verified']),
            )
            ->count();

        $approved = $this->model
            ->newQuery()
            ->whereIn('registration_id', $registrationIds)
            ->currentStatus(['approved', 'verified'])
            ->count();

        $responsiveness = $submitted > 0 ? ($approved / $submitted) * 100 : 0.0;

        return [
            'submitted' => $submitted,
            'approved' => $approved,
            'responsiveness' => round($responsiveness, 2),
        ];
    }
}
