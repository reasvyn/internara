<?php

declare(strict_types=1);

use App\Actions\Internship\TerminatePartnershipAction;
use App\Models\Company;
use App\Models\Partnership;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('terminates an active partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::create([
            'company_id' => $company->id,
            'agreement_number' => '421/PKS/2025',
            'title' => 'Test',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);

        $result = app(TerminatePartnershipAction::class)->execute($partnership);

        expect($result->status->value)->toBe('terminated');
    });

    it('throws when terminating non-active partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::create([
            'company_id' => $company->id,
            'agreement_number' => '421/PKS/2025',
            'title' => 'Test',
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-01',
            'status' => 'expired',
        ]);

        app(TerminatePartnershipAction::class)->execute($partnership);
    })->throws(RuntimeException::class);
});
