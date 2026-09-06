<?php

declare(strict_types=1);

use App\Modules\Partners\Domain\Company\Actions\UpdateCompanyAction;
use App\Modules\Partners\Domain\Company\Data\CompanyData;
use App\Modules\Partners\Domain\Company\Events\CompanyUpdated;
use App\Modules\Partners\Domain\Company\Models\Company;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NTHQA — Company Management — UpdateCompanyAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('NTHQA: UpdateCompanyAction', function (): void {
    test('NTHQA-FR-CC4: updates company fields with valid data', function (): void {
        $company = Company::factory()->create([
            'name' => 'Old Name',
            'address' => 'Old Address',
            'industry_sector' => 'Finance',
        ]);

        $data = new CompanyData(
            name: 'New Name',
            address: 'New Address',
            phone: '+62999999999',
            email: 'new@example.com',
            website: 'https://new.example.com',
            description: 'Updated description',
            industrySector: 'Healthcare',
        );

        $updated = app(UpdateCompanyAction::class)->execute($company, $data);

        expect($updated->name)->toBe('New Name')
            ->and($updated->address)->toBe('New Address')
            ->and($updated->phone)->toBe('+62999999999')
            ->and($updated->email)->toBe('new@example.com')
            ->and($updated->website)->toBe('https://new.example.com')
            ->and($updated->description)->toBe('Updated description')
            ->and($updated->industry_sector)->toBe('Healthcare');
    });

    test('NTHQA-FR-CC4: update persists to database', function (): void {
        $company = Company::factory()->create(['name' => 'Before Update']);

        $data = new CompanyData(name: 'After Update');

        app(UpdateCompanyAction::class)->execute($company, $data);

        expect(Company::find($company->id)->name)->toBe('After Update');
    });

    test('NTHQA-FR-CC5: can update to partial data nulling optional fields', function (): void {
        $company = Company::factory()->create([
            'phone' => '+62111111111',
            'email' => 'old@example.com',
        ]);

        $data = new CompanyData(
            name: $company->name,
            phone: null,
            email: null,
        );

        $updated = app(UpdateCompanyAction::class)->execute($company, $data);

        expect($updated->phone)->toBeNull()
            ->and($updated->email)->toBeNull();
    });

    test('NTHQA-FR-CC6: dispatches CompanyUpdated event', function (): void {
        Event::fake([CompanyUpdated::class]);

        $company = Company::factory()->create();

        $data = new CompanyData(name: 'Updated Name');

        app(UpdateCompanyAction::class)->execute($company, $data);

        Event::assertDispatched(CompanyUpdated::class, fn ($event) => $event->company->id === $company->id);
    });

    test('NTHQA-NFR-R1: wraps update in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(UpdateCompanyAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });
});
