<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Partners\Domain\Company\Actions\BatchDeleteCompanyAction;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NTHQA — Company Batch Delete — BatchDeleteCompanyAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('NTHQA: BatchDeleteCompanyAction', function (): void {
    test('NTHQA-FR-CC11: deletes multiple deletable companies', function (): void {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $result = app(BatchDeleteCompanyAction::class)->execute([$company1->id, $company2->id]);

        expect($result)->toBe(['deleted' => 2, 'blocked' => 0])
            ->and(Company::find($company1->id))->toBeNull()
            ->and(Company::find($company2->id))->toBeNull();
    });

    test('NTHQA-FR-CC12: blocks company with placements in batch', function (): void {
        $companyWithPlacement = Company::factory()->create();
        Placement::factory()->create(['company_id' => $companyWithPlacement->id]);
        $companyWithout = Company::factory()->create();

        $result = app(BatchDeleteCompanyAction::class)->execute([
            $companyWithPlacement->id,
            $companyWithout->id,
        ]);

        expect($result)->toBe(['deleted' => 1, 'blocked' => 1])
            ->and(Company::find($companyWithPlacement->id))->not->toBeNull()
            ->and(Company::find($companyWithout->id))->toBeNull();
    });

    test('NTHQA-FR-CC12: blocks company with partnerships in batch', function (): void {
        $companyWithPartnership = Company::factory()->create();
        Partnership::factory()->create(['company_id' => $companyWithPartnership->id]);
        $companyWithout = Company::factory()->create();

        $result = app(BatchDeleteCompanyAction::class)->execute([
            $companyWithPartnership->id,
            $companyWithout->id,
        ]);

        expect($result)->toBe(['deleted' => 1, 'blocked' => 1])
            ->and(Company::find($companyWithPartnership->id))->not->toBeNull()
            ->and(Company::find($companyWithout->id))->toBeNull();
    });

    test('NTHQA-FR-CC13: skips non-existent IDs gracefully', function (): void {
        $company = Company::factory()->create();

        $result = app(BatchDeleteCompanyAction::class)->execute([$company->id, 'non-existent-id']);

        expect($result)->toBe(['deleted' => 1, 'blocked' => 0]);
    });

    test('NTHQA-FR-CC13: returns zero counts for empty array', function (): void {
        $result = app(BatchDeleteCompanyAction::class)->execute([]);

        expect($result)->toBe(['deleted' => 0, 'blocked' => 0]);
    });
});
