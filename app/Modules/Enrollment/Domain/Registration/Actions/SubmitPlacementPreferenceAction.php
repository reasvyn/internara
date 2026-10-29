<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Data\PlacementPreferenceData;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationStatus;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

final class SubmitPlacementPreferenceAction extends BaseCommandAction
{
    public function execute(Registration $registration, PlacementPreferenceData $data): Registration
    {
        if (in_array($registration->status, [
            RegistrationStatus::CONFIRMED->value,
            RegistrationStatus::ACTIVE->value,
            RegistrationStatus::COMPLETED->value,
        ], true)) {
            throw new RejectedException(__('registration.already_confirmed'));
        }

        if (! $data->placementId && ! $data->proposedCompanyName) {
            throw new RejectedException(__('registration.preference_required'));
        }

        return $this->transaction(function () use ($registration, $data) {
            if ($data->placementId) {
                $placement = Placement::findOrFail($data->placementId);

                if ($placement->asPlacementCapacity()->isFull()) {
                    throw new RejectedException(__('placement.quota_full'));
                }

                $registration->update([
                    'placement_id' => $placement->id,
                    'proposed_company_details' => null,
                ]);
            } else {
                $registration->update([
                    'placement_id' => null,
                    'proposed_company_details' => [
                        'company_name' => $data->proposedCompanyName,
                        'company_address' => $data->proposedCompanyAddress,
                    ],
                ]);
            }

            $registration->setStatus(RegistrationStatus::PENDING);

            $this->log('placement_preference_submitted', $registration, $data->toArray());

            return $registration;
        });
    }
}
