<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Modules\Internship\Models\InternshipRequirement;
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
     * Submit a requirement for a specific registration.
     */
    public function submit(
        string $registrationId,
        string $requirementId,
        mixed $value = null,
        mixed $file = null,
    ): \Modules\Internship\Models\RequirementSubmission;

    /**
     * Verify a requirement submission.
     */
    public function verify(string $submissionId, string $adminId): \Modules\Internship\Models\RequirementSubmission;

    /**
     * Reject a requirement submission with notes.
     */
    public function reject(string $submissionId, string $adminId, string $notes): \Modules\Internship\Models\RequirementSubmission;
}
