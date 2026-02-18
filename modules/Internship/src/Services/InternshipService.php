<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\Internship\Models\Internship;
use Modules\Shared\Services\EloquentQuery;

class InternshipService extends EloquentQuery implements Contracts\InternshipService
{
    public function __construct(
        Internship $internship,
        protected AssignmentService $assignmentService,
    ) {
        $this->setModel($internship);
        $this->setSearchable(['title', 'description', 'year', 'semester']);
        $this->setSortable([
            'title',
            'year',
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
        /** @var Internship $internship */
        $internship = parent::create($data);

        $this->assignmentService->createDefaults($internship->id);

        return $internship;
    }
}
