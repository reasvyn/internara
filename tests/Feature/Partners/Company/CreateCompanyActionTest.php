<?php

declare(strict_types=1);

use App\Partners\Company\Actions\CreateCompanyAction;
use App\Partners\Company\Models\Company;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

test('creates company with valid data', function () {
    $action = app(CreateCompanyAction::class);

    $company = $action->execute(new \App\Partners\Company\Data\CompanyData(
        name: 'PT Teknologi Maju',
        email: 'info@teknologimaju.co.id',
    ));

    expect($company)->toBeInstanceOf(Company::class);
    $this->assertDatabaseHas('companies', ['name' => 'PT Teknologi Maju']);
});

test('creates company with all optional fields', function () {
    $action = app(CreateCompanyAction::class);

    $company = $action->execute(new \App\Partners\Company\Data\CompanyData(
        name: 'CV Karya Digital',
        address: 'Jl. Sudirman No. 10',
        phone: '021-123456',
        email: 'info@karya.digital',
        website: 'https://karya.digital',
        description: 'Software house',
        industrySector: 'technology',
    ));

    expect($company->industry_sector)->toBe('technology');
});

test('creates two companies with unique names', function () {
    $action = app(CreateCompanyAction::class);

    $action->execute(new \App\Partners\Company\Data\CompanyData(name: 'Company A'));
    $action->execute(new \App\Partners\Company\Data\CompanyData(name: 'Company B'));

    $this->assertDatabaseHas('companies', ['name' => 'Company A']);
    $this->assertDatabaseHas('companies', ['name' => 'Company B']);
});