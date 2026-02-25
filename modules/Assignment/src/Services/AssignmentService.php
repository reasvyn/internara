<?php

declare(strict_types=1);

namespace Modules\Assignment\Services;

use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\AssignmentType;
use Modules\Assignment\Services\Contracts\AssignmentService as Contract;
use Modules\Shared\Services\EloquentQuery;

class AssignmentService extends EloquentQuery implements Contract
{
    public function __construct(Assignment $model)
    {
        $this->setModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function createDefaults(string $internshipId, ?string $academicYear = null): void
    {
        $types = AssignmentType::select(['id', 'name', 'group', 'description'])->get();

        foreach ($types as $type) {
            $this->model->newQuery()->firstOrCreate(
                [
                    'assignment_type_id' => $type->id,
                    'internship_id' => $internshipId,
                ],
                [
                    'title' => $type->name,
                    'group' => $type->group,
                    'description' => $type->description,
                    'is_mandatory' => true,
                    'academic_year' => $academicYear,
                ],
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFulfillmentComplete(string $registrationId, ?string $group = null): bool
    {
        // ... (existing logic)
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes(): \Illuminate\Support\Collection
    {
        return AssignmentType::all();
    }
}
