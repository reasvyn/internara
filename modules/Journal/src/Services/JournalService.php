<?php

declare(strict_types=1);

namespace Modules\Journal\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Exception\AppException;
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
    public function __construct()
    {
        $this->setModel(new JournalEntry());
        $this->setSearchable(['work_topic', 'activity_description', 'basic_competence']);
        $this->setSortable(['date', 'created_at']);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function update(mixed $id, array $data): JournalEntry
    {
        $entry = $this->find($id);
        
        // Constraint: Journal cannot be modified after it is approved
        if ($entry->latestStatus()?->name === 'approved') {
            throw new AppException(
                userMessage: 'journal::exceptions.cannot_edit_approved_journal',
                code: 403
            );
        }

        return parent::update($id, $data);
    }

    /**
     * @inheritDoc
     */
    public function submit(mixed $id): JournalEntry
    {
        $entry = $this->find($id);
        $entry->setStatus('submitted', 'Journal submitted by student.');
        
        return $entry;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function reject(mixed $id, string $reason): JournalEntry
    {
        $entry = $this->find($id);
        $entry->setStatus('rejected', $reason);
        
        return $entry;
    }

    /**
     * @inheritDoc
     */
    public function attachMedia(mixed $id, array $files): void
    {
        $entry = $this->find($id);

        foreach ($files as $file) {
            $entry->addMedia($file->getRealPath())
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('attachments', 'private');
        }
    }
}