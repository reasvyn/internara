<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Partners\Domain\Company\Actions\DeleteCompanyAction;
use App\Modules\Partners\Domain\Company\Events\CompanyDeleted;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NTHQA — Company Management — DeleteCompanyAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('NTHQA: DeleteCompanyAction', function (): void {
    test('NTHQA-FR-CC7: deletes company with no placements or partnerships', function (): void {
        $company = Company::factory()->create();

        app(DeleteCompanyAction::class)->execute($company);

        expect(Company::find($company->id))->toBeNull();
    });

    test('NTHQA-FR-CC8: rejects deletion when company has placements', function (): void {
        $company = Company::factory()->create();
        Placement::factory()->create(['company_id' => $company->id]);

        expect(fn () => app(DeleteCompanyAction::class)->execute($company))
            ->toThrow(RejectedException::class);
    });

    test('NTHQA-FR-CC9: rejects deletion when company has partnerships', function (): void {
        $company = Company::factory()->create();
        Partnership::factory()->create(['company_id' => $company->id]);

        expect(fn () => app(DeleteCompanyAction::class)->execute($company))
            ->toThrow(RejectedException::class);
    });

    test('NTHQA-FR-CC10: dispatches CompanyDeleted event', function (): void {
        Event::fake([CompanyDeleted::class]);

        $company = Company::factory()->create();

        app(DeleteCompanyAction::class)->execute($company);

        Event::assertDispatched(CompanyDeleted::class, fn ($event) => $event->company->id === $company->id);
    });

    test('NTHQA-FR-CC7: company still exists after rejected delete attempt', function (): void {
        $company = Company::factory()->create();
        Partnership::factory()->create(['company_id' => $company->id]);

        try {
            app(DeleteCompanyAction::class)->execute($company);
        } catch (RejectedException) {
            // Expected
        }

        expect(Company::find($company->id))->not->toBeNull();
    });

    test('NTHQA-NFR-R1: wraps deletion in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(DeleteCompanyAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });
});
