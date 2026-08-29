<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\School\Actions\SaveSchoolProfileAction;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 81SMS — SaveSchoolProfileAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('81SMS: SaveSchoolProfileAction', function (): void {
    test('81SMS-FR-SP7: extends BaseCommandAction and has correct signature', function (): void {
        $ref = new ReflectionClass(SaveSchoolProfileAction::class);

        expect($ref->getParentClass()?->getName())->toBe(BaseCommandAction::class);

        $method = $ref->getMethod('execute');
        $params = $method->getParameters();

        expect($params)->toHaveCount(2)
            ->and($params[0]->getName())->toBe('data')
            ->and($params[0]->getType()?->getName())->toBe('array')
            ->and($params[1]->getName())->toBe('logoFile')
            ->and($params[1]->allowsNull())->toBeTrue();
    });

    test('81SMS-FR-SP7: has BatchSetSettingAction and UploadBrandAssetAction dependencies', function (): void {
        $source = file_get_contents((new ReflectionClass(SaveSchoolProfileAction::class))->getFileName());

        expect($source)->toContain('BatchSetSettingAction')
            ->and($source)->toContain('UploadBrandAssetAction')
            ->and($source)->toContain('function execute');
    });

    test('81SMS-FR-SP8: executes within transaction for atomicity', function (): void {
        $source = file_get_contents((new ReflectionClass(SaveSchoolProfileAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });

    test('81SMS-FR-SP9: maps each data key to school.{key} SettingEntryData', function (): void {
        $source = file_get_contents((new ReflectionClass(SaveSchoolProfileAction::class))->getFileName());

        expect($source)->toContain('SettingEntryData')
            ->and($source)->toContain('school.{$key}');
    });

    test('81SMS-FR-SP10: calls BatchSetSettingAction for atomic batch upsert', function (): void {
        $payload = [
            'name' => 'SMA 1 Test',
            'institutional_code' => 'NPSN123',
            'email' => 'sma@test.test',
            'address' => 'Jl Test',
            'phone' => '081234',
            'fax' => '021-123',
            'website' => 'https://sma.test',
            'principal_name' => 'Budi',
        ];

        app(SaveSchoolProfileAction::class)->execute($payload);

        foreach ($payload as $key => $value) {
            expect(Setting::where('key', "school.{$key}")->first()?->value)->toBe($value);
        }

        $entity = app(\App\Modules\Academics\Domain\School\Actions\GetSchoolEntityAction::class)->execute();
        expect($entity->name())->toBe('SMA 1 Test')
            ->and($entity->fax())->toBe('021-123');
    });

    test('81SMS-FR-SP12: forgets school_entity cache key after write', function (): void {
        Cache::put(config('cache-keys.school_entity'), 'cached', 60);

        app(SaveSchoolProfileAction::class)->execute(['name' => 'New Name']);

        expect(Cache::has(config('cache-keys.school_entity')))->toBeFalse();
    });

    test('81SMS-FR-SP12: source contains Cache::forget for school_entity', function (): void {
        $source = file_get_contents((new ReflectionClass(SaveSchoolProfileAction::class))->getFileName());

        expect($source)->toContain("Cache::forget")
            ->and($source)->toContain('school_entity');
    });

    test('81SMS-FR-SP11: calls UploadBrandAssetAction when logoFile is provided', function (): void {
        $source = file_get_contents((new ReflectionClass(SaveSchoolProfileAction::class))->getFileName());

        expect($source)->toContain('UploadBrandAssetAction')
            ->and($source)->toContain('logoFile');
    });

    test('81SMS-FR-SP11: accepts UploadedFile without error when provided', function (): void {
        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        app(SaveSchoolProfileAction::class)->execute(['name' => 'With Logo'], $file);

        expect(Setting::where('key', 'school.name')->first()?->value)->toBe('With Logo');
    });

    test('81SMS-FR-SP13: logs school_profile_updated', function (): void {
        $source = file_get_contents((new ReflectionClass(SaveSchoolProfileAction::class))->getFileName());

        expect($source)->toContain('school_profile_updated');
    });
});
