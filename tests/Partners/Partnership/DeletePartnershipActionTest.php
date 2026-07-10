<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Actions\DeletePartnershipAction;
use App\Partners\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('DeletePartnershipAction', function () {
    it('deletes an expired partnership', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->create([
            'company_id' => $company->id,
            'status' => 'expired',
            'end_date' => now()->subDay()->toDateString(),
        ]);

        app(DeletePartnershipAction::class)->execute($partnership);

        expect($partnership->fresh())->toBeNull();
    });

    it('throws RejectedException for active partnerships', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        app(DeletePartnershipAction::class)->execute($partnership);
    })->throws(RejectedException::class, 'Only expired or terminated partnerships can be deleted.');
});
