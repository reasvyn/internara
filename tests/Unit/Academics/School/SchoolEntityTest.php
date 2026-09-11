<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\School\Entities\SchoolEntity;

describe('81SMS: school entity', function (): void {
    test('81SMS-FR-SCH-002: keys maps each property to its school setting key', function (): void {
        $keys = SchoolEntity::keys();

        expect($keys)->toBe([
            'name' => 'school.name',
            'institutional_code' => 'school.institutional_code',
            'email' => 'school.email',
            'address' => 'school.address',
            'phone' => 'school.phone',
            'fax' => 'school.fax',
            'website' => 'school.website',
            'principal_name' => 'school.principal_name',
        ]);
    });

    test('81SMS-FR-SCH-003: fromSettingsArray hydrates all eight properties without a service call', function (): void {
        $entity = SchoolEntity::fromSettingsArray([
            'school.name' => 'SMK Negeri 1',
            'school.institutional_code' => 'NPSN-123',
            'school.email' => 'info@smkn1.test',
            'school.address' => 'Jl. Merdeka 1',
            'school.phone' => '021-111',
            'school.fax' => '021-112',
            'school.website' => 'https://smkn1.test',
            'school.principal_name' => 'Budi Santoso',
        ]);

        expect($entity->name())->toBe('SMK Negeri 1');
        expect($entity->institutionalCode())->toBe('NPSN-123');
        expect($entity->email())->toBe('info@smkn1.test');
        expect($entity->address())->toBe('Jl. Merdeka 1');
        expect($entity->phone())->toBe('021-111');
        expect($entity->fax())->toBe('021-112');
        expect($entity->website())->toBe('https://smkn1.test');
        expect($entity->principalName())->toBe('Budi Santoso');
    });

    test('81SMS-FR-SCH-005: fromSettingsArray defaults missing properties to empty strings', function (): void {
        $entity = SchoolEntity::fromSettingsArray([]);

        expect($entity->name())->toBe('');
        expect($entity->institutionalCode())->toBe('');
        expect($entity->email())->toBe('');
        expect($entity->address())->toBe('');
        expect($entity->phone())->toBe('');
        expect($entity->fax())->toBe('');
        expect($entity->website())->toBe('');
        expect($entity->principalName())->toBe('');
    });

    test('81SMS-FR-SCH-001: fromArray requires name, institutionalCode, and email', function (): void {
        $entity = SchoolEntity::fromArray([
            'name' => 'SMK Negeri 1',
            'institutionalCode' => 'NPSN-123',
            'email' => 'info@smkn1.test',
        ]);

        expect($entity->address())->toBe('');
        expect($entity->principalName())->toBe('');

        expect(fn (): SchoolEntity => SchoolEntity::fromArray(['name' => 'SMK Negeri 1']))
            ->toThrow(InvalidArgumentException::class);
    });

    test('81SMS-FR-SCH-001: toArray, equals, and with round-trip by value', function (): void {
        $entity = SchoolEntity::fromArray([
            'name' => 'SMK Negeri 1',
            'institutionalCode' => 'NPSN-123',
            'email' => 'info@smkn1.test',
        ]);

        expect($entity->toArray())->toBe([
            'name' => 'SMK Negeri 1',
            'institutionalCode' => 'NPSN-123',
            'email' => 'info@smkn1.test',
            'address' => '',
            'phone' => '',
            'fax' => '',
            'website' => '',
            'principalName' => '',
        ]);
        expect($entity->equals(SchoolEntity::fromSettingsArray([
            'school.name' => 'SMK Negeri 1',
            'school.institutional_code' => 'NPSN-123',
            'school.email' => 'info@smkn1.test',
        ])))->toBeTrue();

        $renamed = $entity->with('name', 'SMK Negeri 2');

        expect($renamed->name())->toBe('SMK Negeri 2');
        expect($entity->name())->toBe('SMK Negeri 1');
    });
});
