<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Partners\Partnership\Data\PartnershipData;
use App\Partners\Partnership\Events\PartnershipUpdated;
use App\Partners\Partnership\Models\Partnership;

final class UpdatePartnershipAction extends BaseCommandAction
{
    public function execute(Partnership $partnership, PartnershipData $data): Partnership
    {
        return $this->transaction(function () use ($partnership, $data) {
            $partnership->update([
                'agreement_number' => $data->agreementNumber,
                'title' => $data->title,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'scope' => $data->scope,
                'contact_person_name' => $data->contactPersonName,
                'contact_person_phone' => $data->contactPersonPhone,
                'contact_person_email' => $data->contactPersonEmail,
                'signed_by_school' => $data->signedBySchool,
                'signed_by_company' => $data->signedByCompany,
                'signed_at' => $data->signedAt,
                'notes' => $data->notes,
            ]);

            $this->log('partnership_updated', $partnership, [
                'agreement_number' => $partnership->agreement_number,
            ]);

            event(new PartnershipUpdated($partnership));

            return $partnership->fresh();
        });
    }
}
