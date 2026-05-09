<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Placement;
use Database\Factories\CompanyFactory;
use Database\Factories\PlacementFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $company = CompanyFactory::new()->create();

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->id)->toBeUuid();
});

it('has fillable attributes', function () {
    $company = Company::create([
        'name' => 'Tech Corp',
        'address' => '123 Main St',
        'phone' => '021-12345678',
        'email' => 'info@techcorp.com',
        'website' => 'https://techcorp.com',
        'description' => 'A technology company',
        'industry_sector' => 'Technology',
    ]);

    expect($company->name)->toBe('Tech Corp')
        ->and($company->industry_sector)->toBe('Technology');
});

it('has many placements', function () {
    $company = CompanyFactory::new()->create();
    PlacementFactory::new()->count(2)->create(['company_id' => $company->id]);

    expect($company->placements)->toHaveCount(2)
        ->and($company->placements->first())->toBeInstanceOf(Placement::class);
});
