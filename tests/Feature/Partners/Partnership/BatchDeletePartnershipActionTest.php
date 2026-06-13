<?php

declare(strict_types=1);

use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Actions\BatchDeletePartnershipAction;
use App\Partners\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('BatchDeletePartnershipAction', function () {
    it('deletes multiple expired partnerships', function () {
        $company = Company::factory()->create();
        $partnerships = Partnership::factory()->count(3)->create([
            'company_id' => $company->id,
            'status' => 'expired',
            'end_date' => now()->subDay()->toDateString(),
        ]);
        $ids = $partnerships->pluck('id')->toArray();

        $result = app(BatchDeletePartnershipAction::class)->execute($ids);

        expect($result)->toMatchArray(['deleted' => 3, 'blocked' => 0]);
        foreach ($partnerships as $partnership) {
            expect($partnership->fresh())->toBeNull();
        }
    });

    it('blocks deletion of active partnerships', function () {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $result = app(BatchDeletePartnershipAction::class)->execute([$partnership->id]);

        expect($result)->toMatchArray(['deleted' => 0, 'blocked' => 1]);
        expect($partnership->fresh())->not->toBeNull();
    });

    it('skips non-existent partnership ids', function () {
        $result = app(BatchDeletePartnershipAction::class)->execute(['non-existent-id']);

        expect($result)->toMatchArray(['deleted' => 0, 'blocked' => 0]);
    });

    it('returns empty result for empty ids array', function () {
        $result = app(BatchDeletePartnershipAction::class)->execute([]);

        expect($result)->toMatchArray(['deleted' => 0, 'blocked' => 0]);
    });
});
