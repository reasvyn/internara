<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\SupervisorService as Contract;
use Modules\Shared\Services\EloquentQuery;
use Modules\User\Services\Contracts\UserService;

/**
 * Class SupervisorService
 *
 * Implementation for managing supervisor matching.
 */
class SupervisorService extends EloquentQuery implements Contract
{
    /**
     * SupervisorService constructor.
     */
    public function __construct(protected UserService $userService)
    {
        $this->setModel(new InternshipRegistration);
    }

    /**
     * {@inheritDoc}
     */
    public function assignTeacher(
        InternshipRegistration|string $registration,
        string $teacherId,
    ): bool {
        $registration = $this->resolveRegistration($registration);

        // Validate that the user has the 'teacher' role
        if (! $this->userService->hasRole($teacherId, 'teacher')) {
            return false;
        }

        return $registration->update(['teacher_id' => $teacherId]);
    }

    /**
     * {@inheritDoc}
     */
    public function assignMentor(
        InternshipRegistration|string $registration,
        string $mentorId,
    ): bool {
        $registration = $this->resolveRegistration($registration);

        // Validate that the user has the 'mentor' role
        if (! $this->userService->hasRole($mentorId, 'mentor')) {
            return false;
        }

        return $registration->update(['mentor_id' => $mentorId]);
    }

    /**
     * Resolve registration instance from object or ID.
     */
    protected function resolveRegistration(
        InternshipRegistration|string $registration,
    ): InternshipRegistration {
        if (is_string($registration)) {
            return InternshipRegistration::findOrFail($registration);
        }

        return $registration;
    }
}
