<?php

declare(strict_types=1);

namespace Modules\Assignment\Services;

use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\Submission;
use Modules\Assignment\Services\Contracts\AssignmentService as Contract;
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
     * Get all available assignment types.
     */
    public function getTypes(): \Illuminate\Support\Collection
    {
        return app(\Modules\Assignment\Models\AssignmentType::class)->all();
    }

    /**
     * Get all mandatory assignments for a given internship program.
     */
    public function getMandatoryAssignments(string $internshipId)
    {
        return $this->model->newQuery()
            ->where('internship_id', $internshipId)
            ->where('is_mandatory', true)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function createDefaults(string $internshipId, ?string $academicYear = null): void
    {
        $defaults = [
            [
                'title' => 'Laporan Kegiatan PKL',
                'description' => 'Submit your initial internship report.',
                'type' => 'report',
                'is_mandatory' => true,
            ],
            [
                'title' => 'Presentasi Kegiatan PKL',
                'description' => 'Submit your final presentation slides.',
                'type' => 'presentation',
                'is_mandatory' => true,
            ],
            [
                'title' => 'Sertifikat Industri',
                'description' => 'Bukti sertifikasi industri.',
                'type' => 'certification',
                'is_mandatory' => true,
            ],
            [
                'title' => 'Dokumentasi Teknis',
                'description' => 'Dokumentasi teknis.',
                'type' => 'report',
                'is_mandatory' => true,
            ],
        ];

        foreach ($defaults as $data) {
            $this->create(array_merge($data, [
                'internship_id' => $internshipId,
                'academic_year' => $academicYear,
            ]));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isFulfillmentComplete(string $registrationId, ?string $group = null): bool
    {
        $reg = app(\Modules\Internship\Services\Contracts\RegistrationService::class)->find($registrationId);

        if (!$reg) {
            return false;
        }

        $query = $this->model->newQuery()
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
