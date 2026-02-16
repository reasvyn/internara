<?php

declare(strict_types=1);

namespace Modules\Teacher\Services;

use Modules\Shared\Services\EloquentQuery;
use Modules\Teacher\Models\Teacher;
use Modules\Teacher\Services\Contracts\TeacherService as Contract;

class TeacherService extends EloquentQuery implements Contract
{
    public function __construct(Teacher $model)
    {
        $this->setModel($model);
        $this->setSearchable(['nip']);
    }

    /**
     * {@inheritdoc}
     */
    public function createWithDefault(array $data = []): Teacher
    {
        if (empty($data['nip'])) {
            $data['nip'] = 'PENDING-' . (string) \Illuminate\Support\Str::uuid();
        }

        return $this->create($data);
    }
}
