<?php

declare(strict_types=1);

use App\Enrollment\Placement\Models\Placement;
use App\Partners\Company\Actions\BatchDeleteCompanyAction;
use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('BatchDeleteCompanyAction', function () {
    it('deletes multiple deletable companies', function () {
        $companies = Company::factory()->count(3)->create();
        $ids = $companies->pluck('id')->toArray();

        $result = app(BatchDeleteCompanyAction::class)->execute($ids);

        expect($result)->toMatchArray(['deleted' => 3, 'blocked' => 0]);
        foreach ($companies as $company) {
            expect($company->fresh())->toBeNull();
        }
    });

    it('blocks companies with placements', function () {
        $company = Company::factory()->create();
        Placement::factory()->create(['company_id' => $company->id]);

        $result = app(BatchDeleteCompanyAction::class)->execute([$company->id]);

        expect($result)->toMatchArray(['deleted' => 0, 'blocked' => 1]);
        expect($company->fresh())->not->toBeNull();
    });

    it('blocks companies with partnerships', function () {
        $company = Company::factory()->create();
        Partnership::factory()->create(['company_id' => $company->id]);

        $result = app(BatchDeleteCompanyAction::class)->execute([$company->id]);

        expect($result)->toMatchArray(['deleted' => 0, 'blocked' => 1]);
        expect($company->fresh())->not->toBeNull();
    });

    it('skips non-existent company ids', function () {
        $result = app(BatchDeleteCompanyAction::class)->execute(['non-existent-id']);

        expect($result)->toMatchArray(['deleted' => 0, 'blocked' => 0]);
    });

    it('returns empty result for empty ids array', function () {
        $result = app(BatchDeleteCompanyAction::class)->execute([]);

        expect($result)->toMatchArray(['deleted' => 0, 'blocked' => 0]);
    });
});
