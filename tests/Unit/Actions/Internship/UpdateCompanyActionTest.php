<?php

declare(strict_types=1);

use App\Actions\Internship\UpdateCompanyAction;
use Database\Factories\CompanyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('updates a company', function () {
        $company = CompanyFactory::new()->create();

        $result = app(UpdateCompanyAction::class)->execute($company, [
            'name' => 'Updated Corp',
        ]);

        expect($result->name)->toBe('Updated Corp');
    });
});
