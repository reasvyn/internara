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
