<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Internship\Models\Company;
use Modules\Internship\Models\InternshipPlacement;

uses(RefreshDatabase::class);

test('company model stores master data correctly', function () {
    $company = Company::factory()->create([
        'name' => 'PT. Teknologi Maju',
        'address' => 'Jl. Digital No. 10',
        'business_field' => 'Software Development',
        'phone' => '021-123456',
        'fax' => '021-654321',
        'email' => 'info@tekmaju.com',
        'leader_name' => 'Budi Santoso',
    ]);

    expect($company->name)
        ->toBe('PT. Teknologi Maju')
        ->and($company->address)
        ->toBe('Jl. Digital No. 10')
        ->and($company->business_field)
        ->toBe('Software Development')
        ->and($company->phone)
        ->toBe('021-123456')
        ->and($company->fax)
        ->toBe('021-654321')
        ->and($company->email)
        ->toBe('info@tekmaju.com')
        ->and($company->leader_name)
        ->toBe('Budi Santoso');
});

test('internship placement references company master data', function () {
    $company = Company::factory()->create(['name' => 'Master Company']);
    $placement = InternshipPlacement::factory()->create(['company_id' => $company->id]);

    expect($placement->company->name)->toBe('Master Company');
    expect($placement->company_id)->toBe($company->id);
});
