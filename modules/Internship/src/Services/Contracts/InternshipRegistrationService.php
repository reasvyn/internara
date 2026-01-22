<?php

declare(strict_types=1);

namespace Modules\Internship\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\Internship\Models\InternshipRegistration
 *
 * @extends EloquentQuery<TModel>
 */
interface InternshipRegistrationService extends EloquentQuery
{
    /**
     * Register a student for an internship placement.
     *
     * @param array<string, mixed> $data
     *
     * @throws \Modules\Exception\AppException If no slots available or student already registered.
     */
    public function register(array $data): \Modules\Internship\Models\InternshipRegistration;

    /**
     * Approve a registration.
     */
    public function approve(
        string $registrationId,
    ): \Modules\Internship\Models\InternshipRegistration;

    /**
     * Reject a registration.
     */
    public function reject(
        string $registrationId,
        ?string $reason = null,
    ): \Modules\Internship\Models\InternshipRegistration;

    /**
     * Reassign a student to a different placement.
     */
    public function reassignPlacement(
        string $registrationId,
        string $newPlacementId,
        ?string $reason = null,
    ): \Modules\Internship\Models\InternshipRegistration;
}
