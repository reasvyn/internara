<?php

declare(strict_types=1);

use App\Actions\Internship\CreateCompanyAction;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a company', function () {
        $company = app(CreateCompanyAction::class)->execute([
            'name' => 'Tech Corp',
            'address' => '123 Tech Street',
            'phone' => '1234567890',
            'email' => 'info@techcorp.com',
        ]);

        expect($company)->toBeInstanceOf(Company::class)
            ->and($company->name)->toBe('Tech Corp');
    });
});
