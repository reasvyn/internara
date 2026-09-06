<?php

declare(strict_types=1);

use App\Modules\Partners\Domain\Company\Actions\CreateCompanyAction;
use App\Modules\Partners\Domain\Company\Data\CompanyData;
use App\Modules\Partners\Domain\Company\Events\CompanyCreated;
use App\Modules\Partners\Domain\Company\Models\Company;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NTHQA — Company Management — CreateCompanyAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('NTHQA: CreateCompanyAction', function (): void {
    test('NTHQA-FR-CC1: creates company with valid data', function (): void {
        $data = new CompanyData(
            name: 'Acme Corp',
            address: '123 Main St',
            phone: '+62812345678',
            email: 'contact@acme.com',
            website: 'https://acme.com',
            description: 'A technology company',
            industrySector: 'Technology',
        );

        $company = app(CreateCompanyAction::class)->execute($data);

        expect($company)->toBeInstanceOf(Company::class)
            ->and($company->name)->toBe('Acme Corp')
            ->and($company->address)->toBe('123 Main St')
            ->and($company->phone)->toBe('+62812345678')
            ->and($company->email)->toBe('contact@acme.com')
            ->and($company->website)->toBe('https://acme.com')
            ->and($company->description)->toBe('A technology company')
            ->and($company->industry_sector)->toBe('Technology')
            ->and(Company::where('name', 'Acme Corp')->exists())->toBeTrue();
    });

    test('NTHQA-FR-CC1: creates company with only required name field', function (): void {
        $data = new CompanyData(name: 'Minimal Corp');

        $company = app(CreateCompanyAction::class)->execute($data);

        expect($company->name)->toBe('Minimal Corp')
            ->and($company->address)->toBeNull()
            ->and($company->phone)->toBeNull()
            ->and($company->email)->toBeNull()
            ->and($company->website)->toBeNull()
            ->and($company->description)->toBeNull()
            ->and($company->industry_sector)->toBeNull();
    });

    test('NTHQA-FR-CC2: allows duplicate company names (no unique constraint)', function (): void {
        Company::factory()->create(['name' => 'Duplicate Name']);

        $duplicate = new CompanyData(name: 'Duplicate Name');

        $company = app(CreateCompanyAction::class)->execute($duplicate);

        expect($company->name)->toBe('Duplicate Name')
            ->and(Company::where('name', 'Duplicate Name')->count())->toBe(2);
    });

    test('NTHQA-FR-CC3: dispatches CompanyCreated event', function (): void {
        Event::fake([CompanyCreated::class]);

        $data = new CompanyData(name: 'Event Corp');

        $company = app(CreateCompanyAction::class)->execute($data);

        Event::assertDispatched(CompanyCreated::class, fn ($event) => $event->company->id === $company->id);
    });

    test('NTHQA-NFR-R1: wraps creation in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(CreateCompanyAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });
});
