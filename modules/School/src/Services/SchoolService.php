<?php

declare(strict_types=1);

namespace Modules\School\Services;

use Illuminate\Http\UploadedFile;
use Modules\Exception\AppException;
use Modules\School\Models\School;
use Modules\School\Services\Contracts\SchoolService as SchoolServiceContract;
use Modules\Shared\Services\EloquentQuery;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements the business logic for managing institutional information.
 */
class SchoolService extends EloquentQuery implements SchoolServiceContract
{
    /**
     * Create a new service instance.
     */
    public function __construct(School $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'email']);
    }

    /**
     * Retrieve schools based on conditions.
     */
    public function get(array $filters = [], array $columns = ['*']): \Illuminate\Support\Collection
    {
        return parent::get($filters, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function getSchool(array $columns = ['*']): ?School
    {
        return $this->first([], $columns);
    }

    /**
     * Create a new school record.
     */
    public function create(array $data): School
    {
        if (config('school.single_record') && $this->exists()) {
            throw new AppException(
                userMessage: 'school::exceptions.single_record_exists',
                code: Response::HTTP_CONFLICT,
            );
        }

        /** @var School $school */
        $school = parent::create($data);
        $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

        return $school;
    }

    /**
     * {@inheritdoc}
     */
    public function update(mixed $id, array $data, array $columns = ['*']): School
    {
        /** @var School $school */
        $school = parent::update($id, $data);
        $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

        return $school;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $attributes, array $values = []): School
    {
        $school = parent::save($attributes, $values);
        $allData = array_merge($attributes, $values);
        $this->handleSchoolLogo($school, $allData['logo_file'] ?? null);

        return $school;
    }

    /**
     * Retrieve the first school record.
     */
    public function first(array $filters = [], array $columns = ['*']): ?School
    {
        return parent::first($filters, $columns);
    }

    /**
     * Handle institutional logo update.
     */
    protected function handleSchoolLogo(
        School &$school,
        UploadedFile|string|null $logo = null,
    ): bool {
        return isset($logo) ? $school->setLogo($logo) : false;
    }
}
