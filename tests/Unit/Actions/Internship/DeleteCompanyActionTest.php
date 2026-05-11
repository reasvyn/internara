<?php

declare(strict_types=1);

use App\Actions\Internship\DeleteCompanyAction;
use Database\Factories\CompanyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('deletes a company', function () {
        $company = CompanyFactory::new()->create();

        app(DeleteCompanyAction::class)->execute($company);

        expect($company->fresh())->toBeNull();
    });
});
