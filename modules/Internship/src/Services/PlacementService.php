<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\PlacementLogger;
use Modules\Internship\Services\Contracts\PlacementService as Contract;
use Modules\Shared\Services\EloquentQuery;

class PlacementService extends EloquentQuery implements Contract
{
    /**
     * PlacementService constructor.
     */
    public function __construct(
        InternshipRegistration $model,
        protected PlacementLogger $logger,
        protected InternshipPlacementService $placementService,
    ) {
        $this->setModel($model);
        $this->setBaseQuery($model->newQuery()->with(['user', 'placement', 'internship']));
    }

    /**
     * {@inheritdoc}
     */
    public function isEligibleForPlacement(string $registrationId): bool
    {
        $registration = $this->find($registrationId);

        if (!$registration) {
            return false;
        }

        return $registration->hasClearedAllMandatoryRequirements();
    }

    /**
     * {@inheritdoc}
     */
    public function getEligibleRegistrations(string $academicYear): Collection
    {
        return $this->model
            ->newQuery()
            ->where('academic_year', $academicYear)
            ->whereNull('placement_id')
            ->get()
            ->filter(fn(InternshipRegistration $reg) => $reg->hasClearedAllMandatoryRequirements());
    }

    /**
     * {@inheritdoc}
     */
    public function bulkMatch(array $pairings): int
    {
        $count = 0;

        foreach ($pairings as $registrationId => $placementId) {
            if ($this->assignPlacement($registrationId, $placementId)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function assignPlacement(
        string $registrationId,
        string $placementId,
        ?string $reason = null,
    ): bool {
        return DB::transaction(function () use ($registrationId, $placementId, $reason) {
            $registration = $this->find($registrationId);

            if (!$registration || !$this->isEligibleForPlacement($registrationId)) {
                return false;
            }

            if (!$this->placementService->hasAvailableSlots($placementId)) {
                return false;
            }

            $registration->update(['placement_id' => $placementId]);
            $this->logger->logAssignment($registration, $reason);

            return true;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function changePlacement(
        string $registrationId,
        string $newPlacementId,
        string $reason,
    ): bool {
        return DB::transaction(function () use ($registrationId, $newPlacementId, $reason) {
            $registration = $this->find($registrationId);

            if (!$registration || !$registration->placement_id) {
                return false;
            }

            if (!$this->placementService->hasAvailableSlots($newPlacementId)) {
                return false;
            }

            $oldPlacementId = $registration->placement_id;
            $registration->update(['placement_id' => $newPlacementId]);
            $this->logger->logChange($registration, $oldPlacementId, $newPlacementId, $reason);

            return true;
        });
    }
}
