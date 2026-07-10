<?php

declare(strict_types=1);

use App\Settings\Branding\Data\BrandData;

test('brand data can be created with all fields', function () {
    $data = new BrandData(
        name: 'Internara',
        title: 'Internara - System',
        logo: '/logo.png',
        favicon: '/favicon.ico',
        colors: ['primary' => '#000'],
        version: '1.0',
        authorName: 'Reas Vyn',
        authorEmail: 'reasvyn@gmail.com',
        description: 'System',
        license: 'MIT',
        gitUrl: 'https://github.com/reasvyn/internara',
    );

    expect($data->name)->toBe('Internara');
    expect($data->title)->toBe('Internara - System');
    expect($data->logo)->toBe('/logo.png');
    expect($data->favicon)->toBe('/favicon.ico');
    expect($data->colors)->toBe(['primary' => '#000']);
});

test('brand data is immutable', function () {
    $data = new BrandData(
        name: 'N', title: 'T', logo: '/l.png', favicon: '/f.ico',
        colors: [], version: '1', authorName: 'A', authorEmail: 'a@b.com',
        description: 'd', license: 'MIT', gitUrl: 'https://github.com',
    );

    $reflection = new ReflectionClass($data);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});
