<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\Department\Data\DepartmentData;
use App\Modules\Core\Data\BaseData;

/*
|--------------------------------------------------------------------------
| 4HWSB — Department Management — DepartmentData (spec-driven)
|--------------------------------------------------------------------------
*/

describe('4HWSB: DepartmentData', function (): void {
    test('4HWSB-FR-DM30: extends BaseData', function (): void {
        expect(new DepartmentData('name', 'description', 'id'))->toBeInstanceOf(BaseData::class);
    });

    test('4HWSB-FR-DM30: final readonly class with correct properties', function (): void {
        $ref = new ReflectionClass(DepartmentData::class);

        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();

        $properties = $ref->getProperties();
        $propertyNames = array_map(fn ($p) => $p->getName(), $properties);
        expect($propertyNames)->toBe(['name', 'description', 'id']);
    });

    test('4HWSB-FR-DM30: constructor accepts name, description, id', function (): void {
        $data = new DepartmentData('Test Name', 'Test Description', '123');

        expect($data->name)->toBe('Test Name');
        expect($data->description)->toBe('Test Description');
        expect($data->id)->toBe('123');
    });

    test('4HWSB-FR-DM30: description and id can be null', function (): void {
        $data = new DepartmentData('Test Name', null, null);

        expect($data->name)->toBe('Test Name');
        expect($data->description)->toBeNull();
        expect($data->id)->toBeNull();
    });

    test('4HWSB-FR-DM31: fromArray creates instance from array', function (): void {
        $array = [
            'name' => 'Test Name',
            'description' => 'Test Description',
            'id' => '123',
        ];

        $data = DepartmentData::fromArray($array);

        expect($data->name)->toBe('Test Name');
        expect($data->description)->toBe('Test Description');
        expect($data->id)->toBe('123');
    });

    test('4HWSB-FR-DM31: fromArray handles missing description and id', function (): void {
        $array = [
            'name' => 'Test Name',
        ];

        $data = DepartmentData::fromArray($array);

        expect($data->name)->toBe('Test Name');
        expect($data->description)->toBeNull();
        expect($data->id)->toBeNull();
    });
});
