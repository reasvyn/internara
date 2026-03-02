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
    public function get(
        array $filters = [],
        array $columns = ['*'],
        array $with = [],
    ): \Illuminate\Support\Collection {
        return parent::get($filters, $columns, $with);
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
        \Illuminate\Support\Facades\Gate::authorize('create', School::class);

        if (isset($data['institutional_code']) && strlen((string) $data['institutional_code']) !== 8) {
            throw new AppException(
                userMessage: 'school::exceptions.invalid_institutional_code',
                code: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

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
    public function update(mixed $id, array $data): School
    {
        /** @var School $school */
        $school = parent::update($id, $data);
        $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

        return $school;
    }

    /**
     * Delete a school record and notify other modules.
     */
    public function delete(mixed $id, bool $force = false): bool
    {
        $deleted = parent::delete($id, $force);

        if ($deleted) {
            \Modules\School\Events\SchoolDeleted::dispatch((string) $id);
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $attributes, array $values = []): School
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($attributes, $values) {
            $isSetupAuthorized =
                session(
                    \Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED,
                ) === true;
            $data = array_merge($attributes, $values);
            $schoolId = $data['id'] ?? $this->model->newQuery()->first(['id'])?->id;
            unset($data['id']);

            $query = $isSetupAuthorized ? $this->withoutAuthorization()->query() : $this->query();
            $school = $query->updateOrCreate(['id' => $schoolId], $data);
            $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

            return $school;
        });
    }

    /**
     * Retrieve the first school record.
     */
    public function first(array $filters = [], array $columns = ['*'], array $with = []): ?School
    {
        return parent::first($filters, $columns, $with);
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
