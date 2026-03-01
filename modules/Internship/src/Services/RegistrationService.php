<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipRequirementService;
use Modules\Internship\Services\Contracts\PlacementLogger;
use Modules\Internship\Services\Contracts\RegistrationService as Contract;
use Modules\Shared\Services\EloquentQuery;

class RegistrationService extends EloquentQuery implements Contract
{
    public function __construct(
        InternshipRegistration $model,
        protected InternshipPlacementService $placementService,
        protected InternshipRequirementService $requirementService,
        protected PlacementLogger $logger,
        protected AssignmentService $assignmentService,
    ) {
        $this->setModel($model);
        $this->setSearchable([
            'status',
            'academic_year',
            'student.name',
            'internship.title',
            'placement.company.name',
        ]);
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
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            // 0. Enforce Phase Invariant: Registration restricted by system phase
            if (setting('system_phase', 'registration') !== 'registration') {
                throw new AppException(
                    userMessage: 'internship::exceptions.registration_closed_for_current_phase',
                    code: 403,
                );
            }

            $placementId = $data['placement_id'];
            $studentId = $data['student_id'];

            // 1. Enforce Atomic Lock on Placement to prevent race conditions (BP-PLC-F302)
            \Modules\Internship\Models\InternshipPlacement::where('id', $placementId)
                ->lockForUpdate()
                ->first();

            // 2. Check if student is already registered for THIS internship program
            if (
                $this->exists([
                    'internship_id' => $data['internship_id'],
                    'student_id' => $studentId,
                ])
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

            // 3. Enforce Advisor Invariant: teacher_id is mandatory
            if (empty($data['teacher_id'])) {
                throw new AppException(
                    userMessage: 'internship::exceptions.advisor_required_for_placement',
                    code: 422,
                );
            }

            // 4. Enforce Temporal Integrity: start_date and end_date are mandatory
            if (empty($data['start_date']) || empty($data['end_date'])) {
                throw new AppException(
                    userMessage: 'internship::exceptions.period_dates_required',
                    code: 422,
                );
            }

            if ($data['start_date'] > $data['end_date']) {
                throw new AppException(
                    userMessage: 'internship::exceptions.invalid_period_range',
                    code: 422,
                );
            }

            // 5. Inject active academic year
            $data['academic_year'] = setting('active_academic_year', '2025/2026');

            $registration = $this->create($data);

            // 6. Log initial assignment
            $this->logger->logAssignment($registration);

            return $registration;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function approve(string $registrationId): InternshipRegistration
    {
        $registration = $this->find($registrationId);

        if (! $registration) {
            $e = new \Illuminate\Database\Eloquent\ModelNotFoundException(); throw $e->setModel(
                InternshipRegistration::class,
                [$registrationId],
            );
        }

        // Check if mandatory requirements are cleared via service
        if (! $this->requirementService->hasClearedMandatory($registrationId)) {
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
            $e = new \Illuminate\Database\Eloquent\ModelNotFoundException(); throw $e->setModel(
                InternshipRegistration::class,
                [$registrationId],
            );
        }

        $registration->setStatus('inactive', $reason ?: 'Rejected by administrator');

        return $registration;
    }

    /**
     * {@inheritdoc}
     */
    public function reassignPlacement(
        string $registrationId,
        string $newPlacementId,
        ?string $reason = null,
    ): InternshipRegistration {
        return \Illuminate\Support\Facades\DB::transaction(function () use (
            $registrationId,
            $newPlacementId,
            $reason,
        ) {
            $registration = $this->find($registrationId);

            if (! $registration) {
                $e = new \Illuminate\Database\Eloquent\ModelNotFoundException(); throw $e->setModel(
                    InternshipRegistration::class,
                    [$registrationId],
                );
            }

            $oldPlacementId = $registration->placement_id;

            // 1. Check slot availability for new placement
            if (! $this->placementService->hasAvailableSlots($newPlacementId)) {
                throw new AppException(
                    userMessage: 'internship::exceptions.no_slots_available',
                    code: 422,
                );
            }

            // 2. Log the change
            $this->logger->logChange(
                $registration,
                $oldPlacementId,
                $newPlacementId,
                $reason ?: 'Placement reassignment',
            );

            // 3. Update the registration
            return $this->update($registrationId, [
                'placement_id' => $newPlacementId,
            ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function complete(string $registrationId): InternshipRegistration
    {
        $registration = $this->find($registrationId);

        if (! $registration) {
            $e = new \Illuminate\Database\Eloquent\ModelNotFoundException(); throw $e->setModel(
                InternshipRegistration::class,
                [$registrationId],
            );
        }

        // Assignment Fulfillment Invariant: Completion requires all mandatory assignments verified
        if (! $this->assignmentService->isFulfillmentComplete($registrationId)) {
            throw new AppException(
                userMessage: 'internship::exceptions.mandatory_assignments_not_verified',
                code: 422,
            );
        }

        $registration->setStatus('completed', 'Internship program completed successfully.');

        return $registration;
    }
}
