<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Illuminate\Http\UploadedFile;
use Modules\Internship\Models\InternshipDeliverable;

/**
 * Interface DeliverableService
 *
 * Handles management of student internship deliverables.
 */
interface DeliverableService
{
    /**
     * Submit a deliverable for a registration.
     */
    public function submit(string $registrationId, string $type, UploadedFile $file): InternshipDeliverable;

    /**
     * Verify/Approve a deliverable.
     */
    public function approve(string $deliverableId, ?string $reason = null): InternshipDeliverable;

    /**
     * Reject a deliverable.
     */
    public function reject(string $deliverableId, string $reason): InternshipDeliverable;

    /**
     * Check if all mandatory deliverables are verified for a registration.
     */
    public function areAllDeliverablesVerified(string $registrationId): bool;
}
