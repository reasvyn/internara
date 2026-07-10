<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Actions\RenewPartnershipAction;
use App\Partners\Partnership\Data\PartnershipData;
use App\Partners\Partnership\Models\Partnership;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('RenewPartnershipAction', function () {
    it('renews an expired partnership with new data', function () {
        $company = Company::factory()->create();
        $old = Partnership::factory()->create([
            'company_id' => $company->id,
            'status' => 'expired',
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $newData = PartnershipData::from([
            'company_id' => $company->id,
            'agreement_number' => 'MOU-2026-001',
            'title' => 'Renewed Partnership',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::parse('+1 year')->toDateString(),
            'scope' => 'Extended scope',
            'contact_person_name' => 'John Doe',
        ]);

        $new = app(RenewPartnershipAction::class)->execute($old, $newData);

        expect($new)->toBeInstanceOf(Partnership::class);
        expect($new->agreement_number)->toBe('MOU-2026-001');
        expect($new->company_id)->toBe($company->id);
        expect($new->title)->toBe('Renewed Partnership');
        expect($old->fresh()->status->value)->toBe('expired');
    });

    it('throws RejectedException when renewing an active partnership', function () {
        $company = Company::factory()->create();
        $old = Partnership::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        app(RenewPartnershipAction::class)->execute(
            $old,
            PartnershipData::from(['company_id' => $company->id, 'agreement_number' => 'MOU-2026-002', 'title' => 'Test', 'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString()]),
        );
    })->throws(RejectedException::class, 'Active partnerships must be terminated or expired before renewal.');
});
