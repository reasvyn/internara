<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Modules\Internship\Models\InternshipRegistration;

/**
 * Interface SupervisorService
 * 
 * Handles matching and assignment of Teachers and Mentors to Internship Registrations.
 */
interface SupervisorService
{
    /**
     * Assign a teacher to an internship registration.
     *
     * @param InternshipRegistration|string $registration
     * @param string $teacherId
     * @return bool
     */
    public function assignTeacher(InternshipRegistration|string $registration, string $teacherId): bool;

    /**
     * Assign a mentor to an internship registration.
     *
     * @param InternshipRegistration|string $registration
     * @param string $mentorId
     * @return bool
     */
    public function assignMentor(InternshipRegistration|string $registration, string $mentorId): bool;
}
