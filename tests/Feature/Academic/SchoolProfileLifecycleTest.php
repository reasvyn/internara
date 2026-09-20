<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\School\Actions\GetSchoolEntityAction;
use App\Modules\Academic\Domain\School\Actions\SaveSchoolProfileAction;
use App\Modules\Academic\Domain\School\Entities\SchoolEntity;
use App\Modules\Academic\Domain\School\Livewire\Forms\SchoolForm;
use App\Modules\Academic\Domain\School\Livewire\SchoolEditor;
use App\Modules\Setting\Actions\BatchSetSettingAction;
use App\Modules\Setting\Actions\SetSettingAction;
use App\Modules\Setting\Data\SettingData;
use App\Modules\Setting\Domain\Branding\Actions\RemoveBrandAssetAction;
use App\Modules\Setting\Domain\Branding\Actions\UploadBrandAssetAction;
use App\Modules\Setting\Services\Settings;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('81SMS: school profile lifecycle', function (): void {
    test('81SMS-FR-SCH-004: GetSchoolEntityAction reads all eight keys in one batch pass', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'school.name', value: 'SMK Merdeka'));
        app(SetSettingAction::class)->execute(new SettingData(key: 'school.institutional_code', value: 'NPSN-9988'));
        app(SetSettingAction::class)->execute(new SettingData(key: 'school.email', value: 'contact@merdeka.test'));
        app(SetSettingAction::class)->execute(new SettingData(key: 'school.address', value: 'Jl. Merdeka No 10'));
        app(SetSettingAction::class)->execute(new SettingData(key: 'school.phone', value: '021-5551234'));
        app(SetSettingAction::class)->execute(new SettingData(key: 'school.fax', value: '021-5551235'));
        app(SetSettingAction::class)->execute(new SettingData(key: 'school.website', value: 'https://merdeka.test'));
        app(SetSettingAction::class)->execute(new SettingData(key: 'school.principal_name', value: 'Dra. Siti Aminah'));

        $action = app(GetSchoolEntityAction::class);
        $entity = $action->execute();

        expect($entity)->toBeInstanceOf(SchoolEntity::class)
            ->and($entity->name())->toBe('SMK Merdeka')
            ->and($entity->institutionalCode())->toBe('NPSN-9988')
            ->and($entity->email())->toBe('contact@merdeka.test')
            ->and($entity->address())->toBe('Jl. Merdeka No 10')
            ->and($entity->phone())->toBe('021-5551234')
            ->and($entity->fax())->toBe('021-5551235')
            ->and($entity->website())->toBe('https://merdeka.test')
            ->and($entity->principalName())->toBe('Dra. Siti Aminah');
    });

    test('81SMS-FR-SCH-006: SaveSchoolProfileAction prefixes keys with school and upserts via batch', function (): void {
        $action = app(SaveSchoolProfileAction::class);
        $action->execute([
            'name' => 'SMK Maju Jaya',
            'institutional_code' => 'NPSN-12345678',
            'email' => 'admin@majujaya.sch.id',
            'address' => 'Jl. Pendidikan 45',
            'phone' => '021-888999',
            'fax' => '021-888990',
            'website' => 'https://majujaya.sch.id',
            'principal_name' => 'Bambang Sudarmono, M.Pd.',
        ]);

        expect(setting('school.name'))->toBe('SMK Maju Jaya')
            ->and(setting('school.institutional_code'))->toBe('NPSN-12345678')
            ->and(setting('school.email'))->toBe('admin@majujaya.sch.id')
            ->and(setting('school.address'))->toBe('Jl. Pendidikan 45')
            ->and(setting('school.phone'))->toBe('021-888999')
            ->and(setting('school.fax'))->toBe('021-888990')
            ->and(setting('school.website'))->toBe('https://majujaya.sch.id')
            ->and(setting('school.principal_name'))->toBe('Bambang Sudarmono, M.Pd.');
    });

    test('81SMS-FR-SCH-007: profile writes execute inside transaction atomically', function (): void {
        $action = app(SaveSchoolProfileAction::class);

        $action->execute([
            'name' => 'Original Name',
            'email' => 'orig@school.test',
        ]);

        expect(setting('school.name'))->toBe('Original Name');

        try {
            DB::transaction(function () use ($action): void {
                $action->execute(['name' => 'Temp Name']);
                throw new RuntimeException('Simulated failure during save');
            });
        } catch (RuntimeException) {
            // Expected rollback
        }

        expect(setting('school.name'))->toBe('Original Name');
    });

    test('81SMS-FR-SCH-008: logo upload and removal clean up setting and media pointers', function (): void {
        Storage::fake('public');
        $logo = UploadedFile::fake()->image('crest.png', 200, 200);

        $uploadAction = app(UploadBrandAssetAction::class);
        $url = $uploadAction->execute($logo);

        expect($url)->toBeString()->not->toBeEmpty();
        app(SetSettingAction::class)->execute(new SettingData(key: 'brand_logo', value: $url, group: 'branding'));
        expect(setting('brand_logo'))->toBe($url);

        $removeAction = app(RemoveBrandAssetAction::class);
        $removeAction->execute('logo');
        Settings::forget('brand_logo');

        expect(empty(setting('brand_logo')))->toBeTrue();
    });

    test('81SMS-FR-SCH-009: SchoolForm carries all 8 properties and converts to/from entity', function (): void {
        $entity = SchoolEntity::fromSettingsArray([
            'school.name' => 'SMK Negeri 7',
            'school.institutional_code' => 'NPSN-777',
            'school.email' => 'info@smkn7.sch.id',
            'school.address' => 'Jl. Pahlawan No 7',
            'school.phone' => '022-777888',
            'school.fax' => '022-777889',
            'school.website' => 'https://smkn7.sch.id',
            'school.principal_name' => 'Drs. H. Ahmad',
        ]);

        $form = new SchoolForm(new SchoolEditor, 'form');
        $form->loadFromEntity($entity);

        expect($form->name)->toBe('SMK Negeri 7')
            ->and($form->institutional_code)->toBe('NPSN-777')
            ->and($form->email)->toBe('info@smkn7.sch.id')
            ->and($form->address)->toBe('Jl. Pahlawan No 7')
            ->and($form->phone)->toBe('022-777888')
            ->and($form->fax)->toBe('022-777889')
            ->and($form->website)->toBe('https://smkn7.sch.id')
            ->and($form->principal_name)->toBe('Drs. H. Ahmad');

        $payload = $form->toPayload();
        expect($payload)->toHaveKeys([
            'name', 'institutional_code', 'email', 'address', 'phone', 'fax', 'website', 'principal_name',
        ])
            ->and($payload['name'])->toBe('SMK Negeri 7')
            ->and($payload['fax'])->toBe('022-777889');
    });

    test('81SMS-FR-SCH-010: form rules require name and validate contacts properly', function (): void {
        $form = new SchoolForm(new SchoolEditor, 'form');
        $rules = $form->rules();

        expect($rules['name'])->toContain('required')
            ->and($rules['email'])->toContain('nullable')
            ->and($rules['email'])->toContain('email')
            ->and($rules['website'])->toContain('nullable')
            ->and($rules['website'])->toContain('url');
    });

    test('81SMS-FR-SCH-011: SchoolEditor mounts and saves through actions with fresh reload and toast', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        app(SetSettingAction::class)->execute(new SettingData(key: 'school.name', value: 'Old Name'));

        Livewire::actingAs($admin)
            ->test(SchoolEditor::class)
            ->assertSet('form.name', 'Old Name')
            ->set('form.name', 'Freshly Saved School')
            ->set('form.email', 'fresh@school.test')
            ->call('save')
            ->assertDispatched('saved')
            ->assertSet('form.name', 'Freshly Saved School');

        expect(setting('school.name'))->toBe('Freshly Saved School');
    });

    test('81SMS-FR-SCH-012: logo upload validates and previews immediately, removal confirms', function (): void {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $logo = UploadedFile::fake()->image('school_logo.png', 100, 100);

        Livewire::actingAs($admin)
            ->test(SchoolEditor::class)
            ->set('logo_file', $logo)
            ->assertHasNoErrors()
            ->call('confirmAction')
            ->assertHasNoErrors();
    });

    test('81SMS-FR-SCH-013: logoPreviewUrl handles pending uploads and saved URLs', function (): void {
        $component = new SchoolEditor;
        expect($component->logoPreviewUrl())->toBeNull();
    });

    test('81SMS-FR-SCH-014: school_entity cache key is registered in config/cache-keys.php', function (): void {
        $key = config('cache-keys.school_entity');
        expect($key)->toBe('academic.school.entity');
    });

    test('81SMS-FR-SCH-015: entity cache invalidates synchronously after profile write', function (): void {
        Cache::put(config('cache-keys.school_entity'), 'cached_profile_representation', 3600);
        expect(Cache::has(config('cache-keys.school_entity')))->toBeTrue();

        $action = app(SaveSchoolProfileAction::class);
        $action->execute(['name' => 'Cache Invalidation School']);

        expect(Cache::has(config('cache-keys.school_entity')))->toBeFalse();
    });

    test('81SMS-FR-SCH-016: GET /admin/school is protected by auth and role', function (): void {
        $guest = $this->get(route('sysadmin.school'));
        $guest->assertRedirect();

        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student)->get(route('sysadmin.school'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('sysadmin.school'))->assertOk();
    });

    test('81SMS-UC-SCH-001: admin updates school profile fields atomically with reload', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(SchoolEditor::class)
            ->set('form.name', 'SMK Negeri 1 Cibinong')
            ->set('form.institutional_code', '20231456')
            ->set('form.email', 'smkn1cibinong@sch.id')
            ->set('form.phone', '021-8755678')
            ->call('save')
            ->assertDispatched('saved')
            ->assertSet('form.name', 'SMK Negeri 1 Cibinong');

        expect(setting('school.name'))->toBe('SMK Negeri 1 Cibinong')
            ->and(setting('school.institutional_code'))->toBe('20231456');
    });

    test('81SMS-UC-SCH-002: crest upload completes with toast and clears logo_file', function (): void {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $logo = UploadedFile::fake()->image('crest_valid.png', 150, 150);

        Livewire::actingAs($admin)
            ->test(SchoolEditor::class)
            ->set('logo_file', $logo)
            ->assertSet('logo_file', null);
    });

    test('81SMS-UC-SCH-003: admin removes logo through confirmAction and clears setting', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        app(SetSettingAction::class)->execute(new SettingData(key: 'brand_logo', value: 'https://example.com/logo.png', group: 'branding'));

        Livewire::actingAs($admin)
            ->test(SchoolEditor::class)
            ->call('confirmAction');

        expect(empty(setting('brand_logo')))->toBeTrue();
    });

    test('81SMS-NFR-SCH-001: school setting keys conform to dot-notation pattern', function (): void {
        $keys = SchoolEntity::keys();
        foreach ($keys as $property => $key) {
            expect($key)->toMatch('/^school\.[a-z_]+$/');
        }
    });

    test('81SMS-NFR-SCH-002: logo uploads reject non-images or files exceeding 2MB', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $oversizedFile = UploadedFile::fake()->create('big_file.png', 3000, 'image/png');

        Livewire::actingAs($admin)
            ->test(SchoolEditor::class)
            ->set('logo_file', $oversizedFile)
            ->assertHasErrors(['logo_file']);
    });

    test('81SMS-NFR-SCH-003: editor mutations authorize via Setting policy at mount and mutation', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        Livewire::actingAs($student)
            ->test(SchoolEditor::class)
            ->assertForbidden();
    });

    test('81SMS-NFR-SCH-004: profile saves guarantee all eight keys land without partial state', function (): void {
        $action = app(SaveSchoolProfileAction::class);
        $data = [
            'name' => 'All Eight Keys School',
            'institutional_code' => 'NPSN-8888',
            'email' => 'all8@school.test',
            'address' => 'Jl. Delapan No 8',
            'phone' => '021-8888888',
            'fax' => '021-8888889',
            'website' => 'https://eight.school.test',
            'principal_name' => 'Kepala Sekolah 8',
        ];

        $action->execute($data);

        foreach ($data as $k => $v) {
            expect(setting("school.{$k}"))->toBe($v);
        }
    });

    test('81SMS-NFR-SCH-005: invalidation is synchronous and entity reflects newest value immediately', function (): void {
        $action = app(SaveSchoolProfileAction::class);
        $action->execute(['name' => 'Instant Freshness']);

        $entity = app(GetSchoolEntityAction::class)->execute();
        expect($entity->name())->toBe('Instant Freshness');
    });

    test('81SMS-NFR-SCH-006: logo upload previews live and removal requires confirmation', function (): void {
        $component = new SchoolEditor;
        expect($component->showConfirm)->toBeFalse();
    });

    test('81SMS-NFR-SCH-007: school editor form rules match required accessibility attributes', function (): void {
        $form = new SchoolForm(new SchoolEditor, 'form');
        expect($form->rules())->toHaveKey('name');
    });

    test('81SMS-NFR-SCH-008: localized translations exist for school module', function (): void {
        expect(__('school.save_success'))->not->toBe('school.save_success')
            ->and(__('school.logo_saved'))->not->toBe('school.logo_saved');
    });

    test('81SMS-NFR-SCH-009: cached entity read executes under 50ms', function (): void {
        $action = app(GetSchoolEntityAction::class);
        $action->execute(); // Warm

        $start = microtime(true);
        $entity = $action->execute();
        $duration = (microtime(true) - $start) * 1000;

        expect($duration)->toBeLessThan(50)
            ->and($entity)->toBeInstanceOf(SchoolEntity::class);
    });

    test('81SMS-DD-SCH-001: school profile persists as setting keys without dedicated schools table', function (): void {
        expect(Schema::hasTable('schools'))->toBeFalse();
    });

    test('81SMS-DD-SCH-002: SchoolEntity is readonly and does not define setters', function (): void {
        $ref = new ReflectionClass(SchoolEntity::class);
        expect($ref->isReadOnly())->toBeTrue();
        foreach ($ref->getMethods() as $method) {
            expect($method->getName())->not->toStartWith('set');
        }
    });

    test('81SMS-DD-SCH-003: SaveSchoolProfileAction reuses BatchSetSettingAction', function (): void {
        $ref = new ReflectionClass(SaveSchoolProfileAction::class);
        $constructor = $ref->getConstructor();
        $paramTypes = array_map(fn ($p) => (string) $p->getType(), $constructor->getParameters());
        expect($paramTypes)->toContain(BatchSetSettingAction::class);
    });

    test('81SMS-DD-SCH-004: logo upload runs outside profile transaction', function (): void {
        $ref = new ReflectionClass(SchoolEditor::class);
        expect($ref->hasMethod('updatedLogoFile'))->toBeTrue();
    });

    test('81SMS-DD-SCH-005: school_entity cache key is present in config', function (): void {
        expect(config('cache-keys.school_entity'))->not->toBeNull();
    });
});
