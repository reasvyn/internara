<?php

declare(strict_types=1);

use App\Academics\School\Entities\SchoolEntity;
use App\Core\Entities\BaseEntity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 81SMS — School Profile — SchoolEntity (spec-driven)
|--------------------------------------------------------------------------
*/

describe('81SMS: SchoolEntity', function (): void {
    test('81SMS-FR-SP1: is final readonly and extends BaseEntity', function (): void {
        $ref = new ReflectionClass(SchoolEntity::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue()
            ->and($ref->getParentClass()?->getName())->toBe(BaseEntity::class);
    });

    test('81SMS-FR-SP1: has 8 typed string properties via constructor', function (): void {
        $ref = new ReflectionClass(SchoolEntity::class);
        $params = $ref->getConstructor()?->getParameters() ?? [];

        expect($params)->toHaveCount(8);

        $names = array_map(fn ($p) => $p->getName(), $params);
        expect($names)->toBe(['name', 'institutionalCode', 'email', 'address', 'phone', 'fax', 'website', 'principalName']);

        foreach ($params as $param) {
            expect($param->getType()?->getName())->toBe('string');
        }
    });

    test('81SMS-FR-SP2: defines KEYS mapping 8 school.* keys', function (): void {
        $ref = new ReflectionClass(SchoolEntity::class);
        $keys = $ref->getConstant('KEYS');

        expect($keys)->toBeArray()->toHaveCount(8)
            ->and($keys)->toHaveKey('name', 'school.name')
            ->and($keys)->toHaveKey('fax', 'school.fax')
            ->and(array_values($keys))->each->toStartWith('school.');
    });

    test('81SMS-FR-SP3: has no use App\\Settings\\* import (C5, MOD_XMOD_INTERNAL)', function (): void {
        $source = file_get_contents((new ReflectionClass(SchoolEntity::class))->getFileName());

        expect($source)->not->toContain('use App\\Settings\\');
    });

    test('81SMS-FR-SP3: provides pure fromSettingsArray(array $values): self factory', function (): void {
        $ref = new ReflectionClass(SchoolEntity::class);

        expect($ref->hasMethod('fromSettingsArray'))->toBeTrue();

        $method = $ref->getMethod('fromSettingsArray');
        $returnType = $method->getReturnType()?->getName();
        expect($method->isStatic())->toBeTrue()
            ->and($method->isPublic())->toBeTrue()
            ->and($returnType)->toBeIn([SchoolEntity::class, 'self', 'static']);
    });

    test('81SMS-FR-SP3: fromSettingsArray hydrates all 8 fields correctly', function (): void {
        $values = [
            'school.name' => 'SMA 1 Test',
            'school.institutional_code' => 'NPSN123',
            'school.email' => 'test@school.test',
            'school.address' => 'Jl Test 1',
            'school.phone' => '081234',
            'school.fax' => '021-123',
            'school.website' => 'https://school.test',
            'school.principal_name' => 'Budi',
        ];

        $entity = SchoolEntity::fromSettingsArray($values);

        expect($entity->name())->toBe('SMA 1 Test')
            ->and($entity->institutionalCode())->toBe('NPSN123')
            ->and($entity->email())->toBe('test@school.test')
            ->and($entity->address())->toBe('Jl Test 1')
            ->and($entity->phone())->toBe('081234')
            ->and($entity->fax())->toBe('021-123')
            ->and($entity->website())->toBe('https://school.test')
            ->and($entity->principalName())->toBe('Budi');
    });

    test('81SMS-FR-SP3: fromSettingsArray defaults missing keys to empty string', function (): void {
        $entity = SchoolEntity::fromSettingsArray([]);

        expect($entity->name())->toBe('')
            ->and($entity->email())->toBe('')
            ->and($entity->fax())->toBe('');
    });

    test('81SMS-FR-SP3b: get() delegates via FQCN without use import and returns entity', function (): void {
        $entity = SchoolEntity::get();

        expect($entity)->toBeInstanceOf(SchoolEntity::class);
    });

    test('81SMS-FR-SP3b: get() uses FQCN \\App\\Settings\\Services\\Settings::get (no use)', function (): void {
        $source = file_get_contents((new ReflectionClass(SchoolEntity::class))->getFileName());

        expect($source)->toContain('\\App\\Settings\\Services\\Settings::get')
            ->and($source)->not->toContain('use App\\Settings\\Services\\Settings');
    });

    test('81SMS-FR-SP4: keys() returns KEYS constant', function (): void {
        expect(SchoolEntity::keys())->toBe((new ReflectionClass(SchoolEntity::class))->getConstant('KEYS'));
    });

    test('81SMS-FR-SP5: fromModel() delegates to get() (no Model dependency)', function (): void {
        $source = file_get_contents((new ReflectionClass(SchoolEntity::class))->getFileName());

        expect($source)->toContain('function fromModel')
            ->and($source)->toContain('return self::get()');

        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $entity = SchoolEntity::fromModel($model);

        expect($entity)->toBeInstanceOf(SchoolEntity::class);
    });

    test('81SMS-FR-SP6: provides all 8 accessors returning string', function (): void {
        $entity = new SchoolEntity(
            name: 'N',
            institutionalCode: 'C',
            email: 'E',
            address: 'A',
            phone: 'P',
            fax: 'F',
            website: 'W',
            principalName: 'PN',
        );

        expect($entity->name())->toBe('N')
            ->and($entity->institutionalCode())->toBe('C')
            ->and($entity->email())->toBe('E')
            ->and($entity->address())->toBe('A')
            ->and($entity->phone())->toBe('P')
            ->and($entity->fax())->toBe('F')
            ->and($entity->website())->toBe('W')
            ->and($entity->principalName())->toBe('PN');
    });
});
