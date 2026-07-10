<?php

declare(strict_types=1);

use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Actions\CreatePartnershipAction;
use App\Partners\Partnership\Data\PartnershipData;
use App\Partners\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('creates partnership for company', function () {
    $company = Company::factory()->create();
    $action = app(CreatePartnershipAction::class);

    $partnership = $action->execute(PartnershipData::from([
        'company_id' => $company->id,
        'agreement_number' => 'MOU-001/2026',
        'title' => 'Partnership Agreement',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]));

    expect($partnership)->toBeInstanceOf(Partnership::class);
    $this->assertDatabaseHas('partnerships', ['agreement_number' => 'MOU-001/2026']);
    expect($partnership->status->value)->toBe('active');
});

test('creates partnerships for multiple companies', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $action = app(CreatePartnershipAction::class);

    $action->execute(PartnershipData::from([
        'company_id' => $companyA->id,
        'agreement_number' => 'MOU-A',
        'title' => 'Agreement A',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]));

    $action->execute(PartnershipData::from([
        'company_id' => $companyB->id,
        'agreement_number' => 'MOU-B',
        'title' => 'Agreement B',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
    ]));

    expect(Partnership::count())->toBe(2);
});
