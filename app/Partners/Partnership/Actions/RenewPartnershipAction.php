<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\Partners\Partnership\Data\PartnershipData;
use App\Partners\Partnership\Enums\PartnershipStatus;
use App\Partners\Partnership\Events\PartnershipRenewed;
use App\Partners\Partnership\Models\Partnership;

final class RenewPartnershipAction extends BaseCommandAction
{
    public function __construct(protected readonly CreatePartnershipAction $createPartnership) {}

    public function execute(Partnership $oldPartnership, PartnershipData $newData): Partnership
    {
        if ($oldPartnership->asPartnershipState()->isActive()) {
            throw new RejectedException(
                'Active partnerships must be terminated or expired before renewal.',
            );
        }

        return $this->transaction(function () use ($oldPartnership, $newData) {
            $oldPartnership->update(['status' => PartnershipStatus::EXPIRED->value]);

            $data = PartnershipData::from([
                'company_id' => $oldPartnership->company_id,
                'agreement_number' => $newData->agreementNumber,
                'title' => $newData->title ?? $oldPartnership->title,
                'start_date' => $newData->startDate,
                'end_date' => $newData->endDate,
                'scope' => $newData->scope ?? $oldPartnership->scope,
                'contact_person_name' => $newData->contactPersonName ?? $oldPartnership->contact_person_name,
                'contact_person_phone' => $newData->contactPersonPhone ?? $oldPartnership->contact_person_phone,
                'contact_person_email' => $newData->contactPersonEmail ?? $oldPartnership->contact_person_email,
                'signed_by_school' => $newData->signedBySchool ?? $oldPartnership->signed_by_school,
                'signed_by_company' => $newData->signedByCompany ?? $oldPartnership->signed_by_company,
                'signed_at' => $newData->signedAt ?? now()->toDateString(),
                'notes' => $newData->notes ?? null,
            ]);

            $newPartnership = $this->createPartnership->execute($data);

            event(new PartnershipRenewed($newPartnership, $oldPartnership));

            $this->log('partnership_renewed', $newPartnership, [
                'old_partnership_id' => $oldPartnership->id,
                'new_agreement_number' => $newPartnership->agreement_number,
            ]);

            return $newPartnership;
        });
    }
}
