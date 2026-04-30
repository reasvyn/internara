<?php

declare(strict_types=1);

namespace Modules\Assignment\Services\Contracts;

use Modules\Assignment\Models\Submission;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<Submission>
 */
interface SubmissionService extends EloquentQuery
{
    /**
     * Submit a file or content for a specific assignment.
     */
    public function submit(
        string $registrationId,
        string $assignmentId,
        mixed $content,
    ): Submission;

    /**
     * Verify/Approve a student submission.
     */
    public function verify(string $submissionId, ?string $reason = null): Submission;
}
