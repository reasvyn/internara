<?php

declare(strict_types=1);

namespace Modules\Assignment\Services;

use Modules\Assignment\Models\AssignmentType;
use Modules\Assignment\Services\Contracts\AssignmentTypeService as Contract;
use Modules\Shared\Services\EloquentQuery;

class AssignmentTypeService extends EloquentQuery implements Contract
{
    public function __construct(AssignmentType $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'slug', 'group']);
        $this->setSortable(['name', 'group', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): ?AssignmentType
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }
}
