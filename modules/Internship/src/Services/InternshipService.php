<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\Internship\Enums\ProgramStatus;
use Modules\Internship\Models\Internship;
use Modules\Shared\Services\EloquentQuery;

class InternshipService extends EloquentQuery implements Contracts\InternshipService
{
    public function __construct(
        Internship $internship,
        protected AssignmentService $assignmentService,
    ) {
        $this->setModel($internship);
        $this->setSearchable(['title', 'description', 'academic_year', 'semester']);
        $this->setSortable([
            'title',
            'academic_year',
            'semester',
            'date_start',
            'date_finish',
            'created_at',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Internship
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            /** @var Internship $internship */
            $internship = parent::create($data);

            // Initialize with DRAFT status
            $internship->setStatus(ProgramStatus::DRAFT->value, 'Program initialization');

            $this->assignmentService->createDefaults($internship->id);

            return $internship;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus(string $id, string $status, ?string $reason = null): void
    {
        /** @var Internship $internship */
        $internship = $this->find($id);

        if (! $internship) {
            return;
        }

        $newStatus = ProgramStatus::tryFrom($status);
        if (! $newStatus) {
            throw new \InvalidArgumentException("Invalid program status: {$status}");
        }

        // Business Rule: Opening a program requires it to be at least Published or Draft,
        // and must have a valid future end date. (Enterprise Guard)
        if ($newStatus === ProgramStatus::OPEN) {
            if ($internship->date_finish->isPast()) {
                throw new \Modules\Exception\AppException('internship::exceptions.program_ended_cannot_open', 422);
            }
        }

        $internship->setStatus($status, $reason);
    }
}
