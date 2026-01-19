<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Illuminate\Support\Collection;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\PlacementService as Contract;
use Modules\Shared\Services\EloquentQuery;

class PlacementService extends EloquentQuery implements Contract
{
    /**
     * PlacementService constructor.
     */
    public function __construct(InternshipRegistration $model)
    {
        $this->setModel($model);
        $this->setBaseQuery($model->newQuery()->with(['user', 'placement', 'internship']));
    }

    /**
     * {@inheritdoc}
     */
    public function isEligibleForPlacement(string $registrationId): bool
    {
        $registration = $this->find($registrationId);

        if (! $registration) {
            return false;
        }

        return $registration->hasClearedAllMandatoryRequirements();
    }

    /**
     * {@inheritdoc}
     */
    public function getEligibleRegistrations(string $academicYear): Collection
    {
        return $this->model->newQuery()
            ->where('academic_year', $academicYear)
            ->whereNull('placement_id')
            ->get()
            ->filter(fn (InternshipRegistration $reg) => $reg->hasClearedAllMandatoryRequirements());
    }

    /**
     * {@inheritdoc}
     */
    public function bulkMatch(array $pairings): int
    {
        $count = 0;

        foreach ($pairings as $registrationId => $placementId) {
            $registration = $this->find($registrationId);

            if ($registration && $this->isEligibleForPlacement($registrationId)) {
                $registration->update(['placement_id' => $placementId]);
                $count++;
            }
        }

        return $count;
    }
}
