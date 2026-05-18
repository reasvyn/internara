<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Partnership;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RenewPartnershipAction
{
    public function __construct(
        protected readonly LogAuditAction $logAudit,
        protected readonly CreatePartnershipAction $createPartnership,
    ) {}

    public function execute(Partnership $oldPartnership, array $newData): Partnership
    {
        if ($oldPartnership->asPartnershipState()->isActive()) {
            throw new RuntimeException('Active partnerships must be terminated or expired before renewal.');
        }

        return DB::transaction(function () use ($oldPartnership, $newData) {
            $oldPartnership->update(['status' => 'expired']);

            $data = array_merge([
                'company_id' => $oldPartnership->company_id,
                'agreement_number' => $newData['agreement_number'],
                'title' => $newData['title'] ?? $oldPartnership->title,
                'start_date' => $newData['start_date'],
                'end_date' => $newData['end_date'],
                'scope' => $newData['scope'] ?? $oldPartnership->scope,
                'contact_person_name' => $newData['contact_person_name'] ?? $oldPartnership->contact_person_name,
                'contact_person_phone' => $newData['contact_person_phone'] ?? $oldPartnership->contact_person_phone,
                'contact_person_email' => $newData['contact_person_email'] ?? $oldPartnership->contact_person_email,
                'signed_by_school' => $newData['signed_by_school'] ?? $oldPartnership->signed_by_school,
                'signed_by_company' => $newData['signed_by_company'] ?? $oldPartnership->signed_by_company,
                'signed_at' => $newData['signed_at'] ?? now(),
                'notes' => $newData['notes'] ?? null,
            ], $newData);

            $newPartnership = $this->createPartnership->execute($data);

            $this->logAudit->execute(
                action: 'partnership_renewed',
                subjectType: Partnership::class,
                subjectId: $newPartnership->id,
                payload: [
                    'old_partnership_id' => $oldPartnership->id,
                    'new_agreement_number' => $newPartnership->agreement_number,
                ],
                module: 'Partnership',
            );

            return $newPartnership;
        });
    }
}
