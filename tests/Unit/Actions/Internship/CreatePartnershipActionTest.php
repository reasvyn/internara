<?php

declare(strict_types=1);

use App\Actions\Internship\CreatePartnershipAction;
use App\Models\Company;
use App\Models\Partnership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a partnership', function () {
        $company = Company::factory()->create();

        $partnership = app(CreatePartnershipAction::class)->execute([
            'company_id' => $company->id,
            'agreement_number' => '421/PKS/2025',
            'title' => 'PKL Partnership 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);

        expect($partnership)->toBeInstanceOf(Partnership::class)
            ->and($partnership->agreement_number)->toBe('421/PKS/2025')
            ->and($partnership->status->value)->toBe('active');
    });

    it('validates required fields', function () {
        app(CreatePartnershipAction::class)->execute([]);
    })->throws(ValidationException::class);
});
