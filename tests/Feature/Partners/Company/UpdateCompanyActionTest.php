<?php

declare(strict_types=1);

use App\Partners\Company\Actions\UpdateCompanyAction;
use App\Partners\Company\Models\Company;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

test('updates company name', function () {
    $company = Company::factory()->create(['name' => 'Old Name']);
    $action = app(UpdateCompanyAction::class);

    $action->execute($company, ['name' => 'New Name']);

    expect($company->fresh()->name)->toBe('New Name');
});

test('updates company industry sector', function () {
    $company = Company::factory()->create();
    $action = app(UpdateCompanyAction::class);

    $action->execute($company, ['industry_sector' => 'finance']);

    expect($company->fresh()->industry_sector)->toBe('finance');
});