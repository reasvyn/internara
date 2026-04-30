<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Modules\Internship\Models\InternshipRequirement;
use Modules\Internship\Models\RequirementSubmission;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<InternshipRequirement>
 */
interface InternshipRequirementService extends EloquentQuery
{
    /**
     * Get active requirements for a specific academic year.
     */
    public function getActiveForYear(string $academicYear);

    /**
     * Get institutional summary metrics for internship requirements.
     *
     * @return array{total: int, mandatory: int, active: int, documents: int}
     */
    public function getStats(): array;

    /**
     * Submit a requirement for a specific registration.
     */
    public function submit(
        string $registrationId,
        string $requirementId,
        mixed $value = null,
        mixed $file = null,
    ): RequirementSubmission;

    /**
     * Verify a requirement submission.
     */
    public function verify(string $submissionId, string $adminId): RequirementSubmission;

    /**
     * Reject a requirement submission.
     */
    public function reject(
        string $submissionId,
        string $adminId,
        string $notes,
    ): RequirementSubmission;

    /**
     * Determine if a registration has cleared all mandatory requirements for its academic year.
     */
    public function hasClearedMandatory(string $registrationId): bool;
}
