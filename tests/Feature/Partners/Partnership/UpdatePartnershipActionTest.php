<?php

declare(strict_types=1);

use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Actions\UpdatePartnershipAction;
use App\Partners\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates partnership title', function () {
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->create(['company_id' => $company->id, 'title' => 'Old Title']);
    $action = app(UpdatePartnershipAction::class);

    $action->execute($partnership, [
        'agreement_number' => $partnership->agreement_number,
        'title' => 'New Title',
        'start_date' => $partnership->start_date->toDateString(),
        'end_date' => $partnership->end_date->toDateString(),
    ]);

    expect($partnership->fresh()->title)->toBe('New Title');
});
