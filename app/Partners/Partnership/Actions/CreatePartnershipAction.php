<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Partners\Partnership\Data\PartnershipData;
use App\Partners\Partnership\Events\PartnershipCreated;
use App\Partners\Partnership\Models\Partnership;

final class CreatePartnershipAction extends BaseCommandAction
{
    public function execute(PartnershipData $data): Partnership
    {
        return $this->transaction(function () use ($data) {
            $partnership = Partnership::create([
                'company_id' => $data->companyId,
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

            $this->log('partnership_created', $partnership, [
                'agreement_number' => $partnership->agreement_number,
                'company_id' => $partnership->company_id,
            ]);

            event(new PartnershipCreated($partnership));

            return $partnership;
        });
    }
}
