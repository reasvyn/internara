<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Actions;

use App\Core\Actions\BaseAction;
use App\Partners\Partnership\Models\Partnership;
use Illuminate\Support\Facades\Validator;

final class UpdatePartnershipAction extends BaseAction
{
    public function execute(Partnership $partnership, array $data): Partnership
    {
        $validated = Validator::validate($data, [
            'agreement_number' => 'required|string|max:100|unique:partnerships,agreement_number,'.$partnership->id,
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

        return $this->transaction(function () use ($partnership, $validated) {
            $partnership->update($validated);

            $this->log('partnership_updated', $partnership, ['agreement_number' => $partnership->agreement_number]);

            return $partnership->fresh();
        });
    }
}
