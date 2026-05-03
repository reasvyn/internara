<?php

declare(strict_types=1);

namespace Modules\Assignment\Services;

use Illuminate\Database\QueryException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\AssignmentType;
use Modules\Assignment\Models\Submission;
use Modules\Assignment\Services\Contracts\AssignmentService as Contract;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Shared\Services\EloquentQuery;

class AssignmentService extends EloquentQuery implements Contract
{
    /**
     * AssignmentService constructor.
     */
    public function __construct(Assignment $model)
    {
        $this->setModel($model);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?Assignment
    {
        return $this->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Assignment
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('create', $this->model);
        }

        $this->skipAuthorization = false;

        $filteredData = $this->filterFillable($data);

        try {
            /** @var Assignment $model */
            $model = $this->model->newInstance($filteredData);
            $model->save();

            return $model;
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'create_failed');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(Assignment $assignment, array $data): void
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('update', $assignment);
        }

        $this->skipAuthorization = false;

        $filteredData = $this->filterFillable($data);

        try {
            $assignment->update($filteredData);
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'update_failed');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Assignment $assignment): bool
    {
        return parent::delete($assignment);
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15): Paginator
    {
        return $this->query()->paginate($perPage);
    }

    /**
     * Get all available assignment types.
     */
    public function getTypes(): Collection
    {
        return app(AssignmentType::class)->all();
    }

    /**
     * Get all mandatory assignments for a given internship program.
     */
    public function getMandatoryAssignments(string $internshipId)
    {
        return $this->model
            ->newQuery()
            ->where('internship_id', $internshipId)
            ->where('is_mandatory', true)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createDefaults(string $internshipId, ?string $academicYear = null): void
    {
        $types = app(AssignmentType::class)->all()->pluck('id', 'slug');

        $defaults = [
            [
                'title' => 'Laporan Kegiatan PKL',
                'description' => 'Submit your initial internship report.',
                'assignment_type_id' => $types->get('laporan-pkl'),
                'is_mandatory' => true,
            ],
            [
                'title' => 'Presentasi Kegiatan PKL',
                'description' => 'Submit your final presentation slides.',
                'assignment_type_id' => $types->get('presentasi-pkl'),
                'is_mandatory' => true,
            ],
            [
                'title' => 'Sertifikat Industri',
                'description' => 'Bukti sertifikasi industri.',
                'assignment_type_id' => $types->get('sertifikat-industri'),
                'is_mandatory' => true,
            ],
            [
                'title' => 'Dokumentasi Teknis',
                'description' => 'Dokumentasi teknis.',
                'assignment_type_id' => $types->get('dokumentasi-teknis'),
                'is_mandatory' => true,
            ],
        ];

        foreach ($defaults as $data) {
            if (! $data['assignment_type_id']) {
                continue;
            }

            $this->withoutAuthorization()->create(
                array_merge($data, [
                    'internship_id' => $internshipId,
                    'academic_year' => $academicYear,
                ]),
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isFulfillmentComplete(string $registrationId, ?string $group = null): bool
    {
        $reg = app(RegistrationService::class)->find($registrationId);

        if (! $reg) {
            return false;
        }

        $query = $this->model
            ->newQuery()
            ->where('internship_id', $reg->internship_id)
            ->where('is_mandatory', true);

        if ($group) {
            $query->where('group', $group);
        }

        if ($query->count() === 0) {
            return true;
        }

        // 3. Count Verified Submissions
        $verifiedCount = Submission::where('registration_id', $registrationId)
            ->whereRelation('statuses', 'name', 'verified')
            ->whereIn('assignment_id', $query->pluck('id'))
            ->count();

        return $verifiedCount === $query->count();
    }
}
