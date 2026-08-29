<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\School\Actions\GetSchoolEntityAction;
use App\Modules\Academics\Domain\School\Entities\SchoolEntity;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 81SMS — GetSchoolEntityAction (spec-driven, arch pattern > spec)
|--------------------------------------------------------------------------
*/

describe('81SMS: GetSchoolEntityAction', function (): void {
    test('81SMS-FR-SP16a: extends BaseReadAction', function (): void {
        expect(new GetSchoolEntityAction)->toBeInstanceOf(BaseReadAction::class);
    });

    test('81SMS-FR-SP16a: execute() is the only place calling Settings::get for school keys', function (): void {
        $source = file_get_contents((new ReflectionClass(GetSchoolEntityAction::class))->getFileName());

        expect($source)->toContain('Settings::get')
            ->and($source)->toContain('SchoolEntity::keys()')
            ->and($source)->toContain('SchoolEntity::fromSettingsArray');
    });

    test('81SMS-FR-SP16b: uses single batch query array_values(SchoolEntity::keys())', function (): void {
        $source = file_get_contents((new ReflectionClass(GetSchoolEntityAction::class))->getFileName());

        expect($source)->toContain('array_values(SchoolEntity::keys())');
    });

    test('81SMS-FR-SP3a: returns SchoolEntity hydrated from Settings store', function (): void {
        Setting::factory()->create(['key' => 'school.name', 'value' => 'SMA 1']);
        Setting::factory()->create(['key' => 'school.email', 'value' => 'sma@test.test']);
        Setting::factory()->create(['key' => 'school.fax', 'value' => '021-999']);

        $action = app(GetSchoolEntityAction::class);
        $entity = $action->execute();

        expect($entity)->toBeInstanceOf(SchoolEntity::class)
            ->and($entity->name())->toBe('SMA 1')
            ->and($entity->email())->toBe('sma@test.test')
            ->and($entity->fax())->toBe('021-999');
    });

    test('81SMS-FR-SP3a: returns empty strings when no settings exist', function (): void {
        $entity = app(GetSchoolEntityAction::class)->execute();

        expect($entity->name())->toBe('')
            ->and($entity->email())->toBe('');
    });

    test('81SMS-FR-SP3a: returns all 8 fields correctly when all settings present', function (): void {
        $data = [
            'school.name' => 'N',
            'school.institutional_code' => 'IC',
            'school.email' => 'e@test.test',
            'school.address' => 'Addr',
            'school.phone' => '0811',
            'school.fax' => '021-1',
            'school.website' => 'https://s.test',
            'school.principal_name' => 'Head',
        ];
        foreach ($data as $k => $v) {
            Setting::factory()->create(['key' => $k, 'value' => $v]);
        }

        $entity = app(GetSchoolEntityAction::class)->execute();

        expect($entity->name())->toBe('N')
            ->and($entity->institutionalCode())->toBe('IC')
            ->and($entity->email())->toBe('e@test.test')
            ->and($entity->address())->toBe('Addr')
            ->and($entity->phone())->toBe('0811')
            ->and($entity->fax())->toBe('021-1')
            ->and($entity->website())->toBe('https://s.test')
            ->and($entity->principalName())->toBe('Head');
    });

    test('81SMS-FR-SP16b: is idempotent and does not create settings', function (): void {
        $countBefore = Setting::count();

        app(GetSchoolEntityAction::class)->execute();

        expect(Setting::count())->toBe($countBefore);
    });

    test('81SMS-FR-SP16: SchoolForm::loadFromEntity accepts SchoolEntity from GetSchoolEntityAction', function (): void {
        $formRef = new ReflectionClass(\App\Modules\Academics\Domain\School\Livewire\Forms\SchoolForm::class);
        $method = $formRef->getMethod('loadFromEntity');

        $param = $method->getParameters()[0];
        expect($param->getType()?->getName())->toBe(SchoolEntity::class)
            ->and($param->allowsNull())->toBeTrue();

        $source = file_get_contents($formRef->getFileName());
        expect($source)->toContain('GetSchoolEntityAction');
    });
});
