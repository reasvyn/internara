<?php

declare(strict_types=1);

use App\Partners\Company\Data\CompanyData;

test('company data can be created with name only', function () {
    $data = new CompanyData(name: 'PT Maju');

    expect($data->name)->toBe('PT Maju');
    expect($data->address)->toBeNull();
});

test('company data can be created with all fields', function () {
    $data = new CompanyData(
        name: 'PT Maju',
        address: 'Jakarta',
        phone: '021-1234',
        email: 'info@maju.com',
        website: 'https://maju.com',
        description: 'Tech company',
        industrySector: 'technology',
    );

    expect($data->industrySector)->toBe('technology');
});

test('company data is immutable', function () {
    $data = new CompanyData(name: 'Test');

    $r = new ReflectionClass($data);
    foreach ($r->getProperties() as $p) {
        expect($p->isReadOnly())->toBeTrue();
    }
});