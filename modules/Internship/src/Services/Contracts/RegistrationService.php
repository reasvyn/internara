<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<Model>
 */
interface RegistrationService extends EloquentQuery
{
    /**
     * Initiates the official internship registration process for a student.
     *
     * This method orchestrates the creation of the foundational registration
     * entity, enforcing systemic invariants such as slot availability and
     * the "One-Placement Law" per student.
     *
     * @param array<string, mixed> $data Validated registration attributes.
     *
     * @throws \Modules\Exception\AppException If slot exhaustion or duplicate registration occurs.
     *
     * @return Model The registered entity.
     */
    public function register(array $data): Model;

    /**
     * Transitions a pending registration to an authorized state.
     *
     * Certifies that the student has met all institutional and industrial
     * criteria, unlocking the "Active" status and allowing for subsequent
     * logging activities (Journal/Attendance).
     */
    public function approve(
        string $registrationId,
    ): Model;

    /**
     * Terminates a pending registration with provided justification.
     *
     * Prevents unauthorized or invalid applications from progressing,
     * while capturing the rejection rationale for student feedback.
     */
    public function reject(
        string $registrationId,
        ?string $reason = null,
    ): Model;

    /**
     * Moves an active student to a different industrial placement.
     *
     * Handles the technical cleanup of current slot associations while
     * establishing new ones, maintaining transactional integrity across
     * the transfer.
     */
    public function reassignPlacement(
        string $registrationId,
        string $newPlacementId,
        ?string $reason = null,
    ): Model;

    /**
     * Formally closes the internship lifecycle for a student.
     *
     * Verification: Implementation MUST ensure that all mandatory
     * assignments and evaluations are cleared before allowing this transition.
     */
    public function complete(
        string $registrationId,
    ): Model;
}
