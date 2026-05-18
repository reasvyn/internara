<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Partnership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreatePartnershipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): Partnership
    {
        $validated = Validator::validate($data, [
            'company_id' => 'required|exists:internship_companies,id',
            'agreement_number' => 'required|string|max:100|unique:partnerships,agreement_number',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'scope' => 'nullable|string|max:5000',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_person_phone' => 'nullable|string|max:30',
            'contact_person_email' => 'nullable|email|max:255',
            'signed_by_school' => 'nullable|string|max:255',
            'signed_by_company' => 'nullable|string|max:255',
            'signed_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $partnership = Partnership::create($validated);

            $this->logAudit->execute(
                action: 'partnership_created',
                subjectType: Partnership::class,
                subjectId: $partnership->id,
                payload: ['agreement_number' => $partnership->agreement_number, 'company_id' => $partnership->company_id],
                module: 'Partnership',
            );

            return $partnership;
        });
    }
}
