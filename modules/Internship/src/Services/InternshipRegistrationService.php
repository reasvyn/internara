<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipRegistrationService as Contract;
use Modules\Shared\Services\EloquentQuery;

class InternshipRegistrationService extends EloquentQuery implements Contract
{
    public function __construct(
        InternshipRegistration $model,
        protected InternshipPlacementService $placementService,
    ) {
        $this->setModel($model);
        $this->setSortable(['created_at']);
    }

    /**
     * {@inheritDoc}
     */
    protected function applyFilters(&$query, array &$filters): void
    {
        if (isset($filters['latest_status'])) {
            $query->currentStatus($filters['latest_status']);
            unset($filters['latest_status']);
        }

        parent::applyFilters($query, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function register(array $data): InternshipRegistration
    {
        $placementId = $data['placement_id'];
        $studentId = $data['student_id'];

        // 1. Check if student is already registered for THIS internship program
        if (
            $this->exists(['internship_id' => $data['internship_id'], 'student_id' => $studentId])
        ) {
            throw new AppException(
                userMessage: 'internship::exceptions.student_already_registered',
                code: 422,
            );
        }

        // 2. Check slot availability
        if (! $this->placementService->hasAvailableSlots($placementId)) {
            throw new AppException(
                userMessage: 'internship::exceptions.no_slots_available',
                code: 422,
            );
        }

        // 3. Inject active academic year
        $data['academic_year'] = setting('active_academic_year', '2025/2026');

        return $this->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function approve(string $registrationId): InternshipRegistration
    {
        $registration = $this->find($registrationId);

        if (! $registration) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(
                InternshipRegistration::class,
                [$registrationId],
            );
        }

        // Check if mandatory requirements are cleared
        if (! $registration->hasClearedAllMandatoryRequirements()) {
            throw new AppException(
                userMessage: 'internship::exceptions.mandatory_requirements_not_met',
                code: 422,
            );
        }

        $registration->setStatus('active', 'Approved by administrator');

        return $registration;
    }

    /**
     * {@inheritdoc}
     */
    public function reject(string $registrationId, ?string $reason = null): InternshipRegistration
    {
        $registration = $this->find($registrationId);

        if (! $registration) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(
                InternshipRegistration::class,
                [$registrationId],
            );
        }

        $registration->setStatus('inactive', $reason ?: 'Rejected by administrator');

        return $registration;
    }
}
