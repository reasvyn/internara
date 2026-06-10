<?php

declare(strict_types=1);

use App\Partners\Company\Actions\DeleteCompanyAction;
use App\Partners\Company\Models\Company;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes company without related records', function () {
    $company = Company::factory()->create();
    $action = app(DeleteCompanyAction::class);

    $action->execute($company);

    $this->assertDatabaseMissing('companies', ['id' => $company->id]);
});
