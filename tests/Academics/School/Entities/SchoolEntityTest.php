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
| FR-SP1: final readonly class extending BaseEntity with 8 typed string props
| FR-SP2: KEYS constant mapping 8 school.* keys
| FR-SP3: pure — no Settings import, provides fromSettingsArray
| FR-SP3a/b: get() legacy compat delegates via FQCN
| FR-SP4: keys() returns KEYS
| FR-SP5: fromModel() delegates to get()
| FR-SP6: named accessors
*/

describe('81SMS-FR-SP1: SchoolEntity class contract', function (): void {
    it('is final readonly and extends BaseEntity', function (): void {
        $ref = new ReflectionClass(SchoolEntity::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue()
            ->and($ref->getParentClass()?->getName())->toBe(BaseEntity::class);
    });

    it('has 8 typed string properties via constructor', function (): void {
        $ref = new ReflectionClass(SchoolEntity::class);
        $params = $ref->getConstructor()?->getParameters() ?? [];

        expect($params)->toHaveCount(8);

        $names = array_map(fn ($p) => $p->getName(), $params);
        expect($names)->toBe(['name', 'institutionalCode', 'email', 'address', 'phone', 'fax', 'website', 'principalName']);

        foreach ($params as $param) {
            expect($param->getType()?->getName())->toBe('string');
        }
    });
});

describe('81SMS-FR-SP2: KEYS constant', function (): void {
    it('defines KEYS mapping 8 school.* keys', function (): void {
        $ref = new ReflectionClass(SchoolEntity::class);
        $keys = $ref->getConstant('KEYS');

        expect($keys)->toBeArray()->toHaveCount(8)
            ->and($keys)->toHaveKey('name', 'school.name')
            ->and($keys)->toHaveKey('fax', 'school.fax')
            ->and(array_values($keys))->each->toStartWith('school.');
    });
});

describe('81SMS-FR-SP3: SchoolEntity purity (arch pattern > spec)', function (): void {
    it('has no use App\\Settings\\* import (C5, MOD_XMOD_INTERNAL)', function (): void {
        $source = file_get_contents((new ReflectionClass(SchoolEntity::class))->getFileName());

        expect($source)->not->toContain('use App\\Settings\\');
    });

    it('provides pure fromSettingsArray(array $values): self factory', function (): void {
        $ref = new ReflectionClass(SchoolEntity::class);

        expect($ref->hasMethod('fromSettingsArray'))->toBeTrue();

        $method = $ref->getMethod('fromSettingsArray');
        $returnType = $method->getReturnType()?->getName();
        expect($method->isStatic())->toBeTrue()
            ->and($method->isPublic())->toBeTrue()
            ->and($returnType)->toBeIn([SchoolEntity::class, 'self', 'static']);
    });

    it('fromSettingsArray hydrates all 8 fields correctly', function (): void {
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

    it('fromSettingsArray defaults missing keys to empty string', function (): void {
        $entity = SchoolEntity::fromSettingsArray([]);

        expect($entity->name())->toBe('')
            ->and($entity->email())->toBe('')
            ->and($entity->fax())->toBe('');
    });
});

describe('81SMS-FR-SP3b: SchoolEntity::get() legacy compat', function (): void {
    it('get() delegates via FQCN without use import and returns entity', function (): void {
        // Ensure get() exists and does not have Settings use import (checked above)
        // Functional check: get() should return SchoolEntity (may be empty if no settings)
        $entity = SchoolEntity::get();

        expect($entity)->toBeInstanceOf(SchoolEntity::class);
    });

    it('get() uses FQCN \\App\\Settings\\Services\\Settings::get (no use)', function (): void {
        $source = file_get_contents((new ReflectionClass(SchoolEntity::class))->getFileName());

        // Must contain FQCN for backward compat, but not a use import
        expect($source)->toContain('\\App\\Settings\\Services\\Settings::get')
            ->and($source)->not->toContain('use App\\Settings\\Services\\Settings');
    });
});

describe('81SMS-FR-SP4: keys()', function (): void {
    it('returns KEYS constant', function (): void {
        expect(SchoolEntity::keys())->toBe((new ReflectionClass(SchoolEntity::class))->getConstant('KEYS'));
    });
});

describe('81SMS-FR-SP5: fromModel()', function (): void {
    it('delegates to get() (no Model dependency)', function (): void {
        $source = file_get_contents((new ReflectionClass(SchoolEntity::class))->getFileName());

        // fromModel should call get()
        expect($source)->toContain('function fromModel')
            ->and($source)->toContain('return self::get()');

        // Functional: fromModel with dummy model returns entity
        $model = Mockery::mock(\Illuminate\Database\Eloquent\Model::class);
        $entity = SchoolEntity::fromModel($model);

        expect($entity)->toBeInstanceOf(SchoolEntity::class);
    });
});

describe('81SMS-FR-SP6: named accessors', function (): void {
    it('provides all 8 accessors returning string', function (): void {
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
