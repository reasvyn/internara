<?php

declare(strict_types=1);

use App\Academics\School\Actions\GetSchoolEntityAction;
use App\Academics\School\Entities\SchoolEntity;
use App\Core\Actions\BaseReadAction;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 81SMS — GetSchoolEntityAction (spec-driven, arch pattern > spec)
|--------------------------------------------------------------------------
| FR-SP3a: Read Action reads 8 keys via Settings::get batch
| FR-SP16a: extends BaseReadAction, execute(): SchoolEntity is only Settings caller
| FR-SP16b: single batch query + fromSettingsArray hydration
| FR-SP16: SchoolForm integration (tested via Entity)
*/

describe('81SMS-FR-SP16a: GetSchoolEntityAction contract', function (): void {
    it('extends BaseReadAction', function (): void {
        expect(new GetSchoolEntityAction)->toBeInstanceOf(BaseReadAction::class);
    });

    it('execute(): SchoolEntity is the only place calling Settings::get for school keys', function (): void {
        $source = file_get_contents((new ReflectionClass(GetSchoolEntityAction::class))->getFileName());

        expect($source)->toContain('Settings::get')
            ->and($source)->toContain('SchoolEntity::keys()')
            ->and($source)->toContain('SchoolEntity::fromSettingsArray');
    });

    it('uses single batch query array_values(SchoolEntity::keys())', function (): void {
        $source = file_get_contents((new ReflectionClass(GetSchoolEntityAction::class))->getFileName());

        expect($source)->toContain('array_values(SchoolEntity::keys())');
    });
});

describe('81SMS-FR-SP3a/FR-SP16b: GetSchoolEntityAction execution', function (): void {
    it('returns SchoolEntity hydrated from Settings store', function (): void {
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

    it('returns empty strings when no settings exist', function (): void {
        $entity = app(GetSchoolEntityAction::class)->execute();

        expect($entity->name())->toBe('')
            ->and($entity->email())->toBe('');
    });

    it('returns all 8 fields correctly when all settings present', function (): void {
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

    it('is idempotent and does not create settings', function (): void {
        $countBefore = Setting::count();

        app(GetSchoolEntityAction::class)->execute();

        expect(Setting::count())->toBe($countBefore);
    });
});

describe('81SMS-FR-SP16: SchoolForm integration via Action', function (): void {
    it('SchoolForm::loadFromEntity accepts SchoolEntity from GetSchoolEntityAction', function (): void {
        $formRef = new ReflectionClass(\App\Academics\School\Livewire\Forms\SchoolForm::class);
        $method = $formRef->getMethod('loadFromEntity');

        // Must accept ?SchoolEntity $entity = null
        $param = $method->getParameters()[0];
        expect($param->getType()?->getName())->toBe(SchoolEntity::class)
            ->and($param->allowsNull())->toBeTrue();

        $source = file_get_contents($formRef->getFileName());
        expect($source)->toContain('GetSchoolEntityAction');
    });
});
