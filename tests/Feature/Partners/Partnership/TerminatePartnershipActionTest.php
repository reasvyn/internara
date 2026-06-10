<?php

declare(strict_types=1);

use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Actions\TerminatePartnershipAction;
use App\Partners\Partnership\Models\Partnership;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('terminates active partnership', function () {
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $action = app(TerminatePartnershipAction::class);

    $action->execute($partnership);

    expect($partnership->fresh()->status->value)->toBe('terminated');
});

test('cannot terminate already terminated partnership', function () {
    $company = Company::factory()->create();
    $partnership = Partnership::factory()->create([
        'company_id' => $company->id,
        'status' => 'terminated',
    ]);
    $action = app(TerminatePartnershipAction::class);

    expect(fn () => $action->execute($partnership))->toThrow(RuntimeException::class);
});
