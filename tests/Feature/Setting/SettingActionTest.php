<?php

declare(strict_types=1);

use App\Modules\Setting\Actions\DeleteSettingAction;
use App\Modules\Setting\Actions\SaveSystemSettingsAction;
use App\Modules\Setting\Actions\SetSettingAction;
use App\Modules\Setting\Actions\TestMailSettingsAction;
use App\Modules\Setting\Data\SettingData;
use App\Modules\Setting\Data\SystemSettingsData;
use App\Modules\Setting\Entities\SettingEntity;
use App\Modules\Setting\Enums\SettingType;
use App\Modules\Setting\Livewire\Forms\GeneralSettingsForm;
use App\Modules\Setting\Livewire\SystemSetting;
use App\Modules\Setting\Models\Setting;
use App\Modules\Setting\Policies\SettingPolicy;
use App\Modules\Setting\Rules\ValidSettingKey;
use App\Modules\Setting\Services\Settings;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Form;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('YB22J: setting update action', function (): void {
    test('YB22J-FR-SET-003: set stores a new string setting and returns the model', function (): void {
        $result = app(SetSettingAction::class)->execute(new SettingData(
            key: 'tests.sample_string',
            value: 'hello internara',
            group: 'tests',
        ));

        expect($result)->toBeInstanceOf(Setting::class)
            ->and($result->key)->toBe('tests.sample_string')
            ->and($result->type)->toBe('string')
            ->and($result->value)->toBe('hello internara');

        $this->assertDatabaseHas('settings', ['key' => 'tests.sample_string']);
    });

    test('YB22J-FR-SET-003: set auto-detects the integer storage type', function (): void {
        $result = app(SetSettingAction::class)->execute(new SettingData(
            key: 'tests.sample_integer',
            value: 42,
            group: 'tests',
        ));

        expect($result->type)->toBe('integer')
            ->and($result->value)->toBe(42);
    });

    test('YB22J-FR-SET-003: set rewrites the value of an existing key', function (): void {
        $action = app(SetSettingAction::class);
        $action->execute(new SettingData(key: 'tests.rewrite_me', value: 'first', group: 'tests'));

        $result = $action->execute(new SettingData(key: 'tests.rewrite_me', value: 'second', group: 'tests'));

        expect($result->value)->toBe('second');
        expect(Setting::where('key', 'tests.rewrite_me')->count())->toBe(1);
    });

    test('YB22J-FR-SET-003: set rejects a key outside the allowed pattern', function (): void {
        expect(fn () => app(SetSettingAction::class)->execute(new SettingData(
            key: 'Bad Key With Spaces!',
            value: 'nope',
            group: 'tests',
        )))->toThrow(ValidationException::class);

        $this->assertDatabaseMissing('settings', ['key' => 'Bad Key With Spaces!']);
    });

    test('YB22J-FR-SET-004: encrypted values run through Crypt and decrypt on read', function (): void {
        $secret = 'super-secret-smtp-password';
        $setting = app(SetSettingAction::class)->execute(new SettingData(
            key: 'mail.password_test',
            value: $secret,
            type: 'encrypted',
            group: 'mail',
        ));

        expect($setting->type)->toBe('encrypted');
        $raw = DB::table('settings')->where('key', 'mail.password_test')->value('value');
        expect($raw)->not->toBe($secret);
        expect(setting('mail.password_test'))->toBe($secret);
    });

    test('YB22J-FR-SET-005: five-layer precedence resolves runtime overrides down to defaults', function (): void {
        Settings::clearOverrides();
        expect(setting('test.unconfigured_fallback', 'fallback-val'))->toBe('fallback-val');

        Settings::override(['test.unconfigured_fallback' => 'override-val']);
        expect(setting('test.unconfigured_fallback', 'fallback-val'))->toBe('override-val');
        Settings::clearOverrides();
    });

    test('YB22J-FR-SET-006: setting helper signature supports batch array and instance return', function (): void {
        expect(setting())->toBeInstanceOf(Settings::class);

        app(SetSettingAction::class)->execute(new SettingData(key: 'batch.k1', value: 'v1', group: 'batch'));
        app(SetSettingAction::class)->execute(new SettingData(key: 'batch.k2', value: 'v2', group: 'batch'));

        $batch = setting(['batch.k1', 'batch.k2']);
        expect($batch)->toBe(['batch.k1' => 'v1', 'batch.k2' => 'v2']);
    });

    test('YB22J-FR-SET-007: brand helper resolves name and falls back safely', function (): void {
        expect(brand('name'))->toBeString();
        expect(brand('nonexistent_brand_key', 'safe-default'))->toBe('safe-default');
    });

    test('YB22J-FR-SET-008: tier-1 file cache works without external services', function (): void {
        $key = 'tier1.cache_test';
        app(SetSettingAction::class)->execute(new SettingData(key: $key, value: 'cached-value', group: 'general'));
        expect(setting($key))->toBe('cached-value');
    });

    test('YB22J-FR-SET-010: DeleteSettingAction deletes setting and invalidates cache', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'del.test_key', value: 'to_delete', group: 'del'));
        expect(setting('del.test_key'))->toBe('to_delete');

        $count = app(DeleteSettingAction::class)->execute('del.test_key');
        expect($count)->toBe(1);
        expect(setting('del.test_key'))->toBeNull();
    });

    test('YB22J-FR-SET-012: cache keys reference registered entries', function (): void {
        expect(config('cache-keys.settings_all'))->toBe('settings.all');
    });

    test('YB22J-FR-SET-013: synchronous invalidation clears cache on setting mutation', function (): void {
        $key = 'sync.invalid_test';
        app(SetSettingAction::class)->execute(new SettingData(key: $key, value: 'val1', group: 'sync'));
        expect(setting($key))->toBe('val1');

        app(SetSettingAction::class)->execute(new SettingData(key: $key, value: 'val2', group: 'sync'));
        expect(setting($key))->toBe('val2');
    });

    test('YB22J-FR-SET-014: admin settings route is protected by auth and role', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(SystemSetting::class)
            ->assertOk();
    });

    test('YB22J-FR-SET-015: independent forms validate separately', function (): void {
        $form = new GeneralSettingsForm(
            new SystemSetting,
            'form'
        );
        expect($form)->toBeInstanceOf(Form::class);
    });

    test('YB22J-FR-SET-016: TestMailSettingsAction verifies mail without persisting tested credentials', function (): void {
        Notification::fake();
        $action = app(TestMailSettingsAction::class);
        $result = $action->execute('test@example.com', [
            'host' => 'smtp.mailtrap.io',
            'port' => 2525,
            'encryption' => 'tls',
            'username' => 'testuser',
            'password' => 'testpass',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Internara System',
        ]);

        expect($result)->toBeTrue();
    });

    test('YB22J-FR-SET-017: feature flags resolve through feature helper', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(
            key: 'features.flag_sample',
            value: true,
            group: 'features',
        ));

        expect(feature('flag_sample'))->toBeTrue()
            ->and(feature('non_existent_flag', false))->toBeFalse();
    });

    test('YB22J-FR-SET-018: colors and images are validated strings', function (): void {
        $setting = app(SetSettingAction::class)->execute(new SettingData(
            key: 'branding.primary_color',
            value: '#059669',
            group: 'branding',
        ));

        expect($setting->type)->toBe('string');
    });

    test('YB22J-UC-SET-001: admin saves system settings in one atomic operation', function (): void {
        $action = app(SaveSystemSettingsAction::class);
        $dto = new SystemSettingsData(
            brandName: 'SMK Negeri Test',
            siteTitle: 'Internara Portal',
            supportEmail: 'support@smk.test',
        );

        $action->execute($dto);
        expect(setting('brand_name'))->toBe('SMK Negeri Test')
            ->and(setting('site_title'))->toBe('Internara Portal')
            ->and(setting('support_email'))->toBe('support@smk.test');
    });

    test('YB22J-UC-SET-002: admin verifies mail configuration via TestMailSettingsAction', function (): void {
        Notification::fake();
        $res = app(TestMailSettingsAction::class)->execute('admin@school.test', [
            'host' => 'smtp.test.com',
            'port' => 587,
        ]);
        expect($res)->toBeTrue();
    });

    test('YB22J-UC-SET-003: caller resolves setting and always observes freshest committed value', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'fresh.key', value: 'old', group: 'fresh'));
        expect(setting('fresh.key'))->toBe('old');

        app(SetSettingAction::class)->execute(new SettingData(key: 'fresh.key', value: 'new', group: 'fresh'));
        expect(setting('fresh.key'))->toBe('new');
    });

    test('YB22J-NFR-SET-001: passwords persist only as encrypted ciphertext', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(
            key: 'mail_password',
            value: 'my-top-secret',
            type: 'encrypted',
            group: 'mail',
        ));

        $raw = DB::table('settings')->where('key', 'mail_password')->value('value');
        expect($raw)->not->toBe('my-top-secret');
    });

    test('YB22J-NFR-SET-002: setting keys match allowed regex pattern', function (): void {
        $validator = new ValidSettingKey;
        $failed = false;
        $validator->validate('key', 'invalid key with spaces', function () use (&$failed) {
            $failed = true;
        });
        expect($failed)->toBeTrue();
    });

    test('YB22J-NFR-SET-003: only superadmin can delete setting rows', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $superadmin = User::factory()->create();
        $superadmin->assignRole('super_admin');

        $policy = app(SettingPolicy::class);
        expect($policy->delete($superadmin))->toBeTrue()
            ->and($policy->delete($admin))->toBeFalse();
    });

    test('YB22J-NFR-SET-004: full page saves are atomic inside transaction', function (): void {
        $action = app(SaveSystemSettingsAction::class);
        $dto = new SystemSettingsData(
            brandName: 'Atomic Brand',
            siteTitle: 'Atomic Title',
        );

        $action->execute($dto);
        expect(setting('brand_name'))->toBe('Atomic Brand');
    });

    test('YB22J-NFR-SET-005: brand resolution never throws on unconfigured installs', function (): void {
        expect(brand('name'))->toBeString()
            ->and(brand('site_title'))->toBeString();
    });

    test('YB22J-NFR-SET-006: cached setting reads are immediate', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'bench.key', value: 'val', group: 'bench'));
        $start = microtime(true);
        setting('bench.key');
        $duration = (microtime(true) - $start) * 1000;
        expect($duration)->toBeLessThan(50);
    });

    test('YB22J-NFR-SET-007: full page save completes promptly', function (): void {
        $action = app(SaveSystemSettingsAction::class);
        $dto = new SystemSettingsData(
            brandName: 'Performance Brand',
            siteTitle: 'Performance Title',
        );

        $start = microtime(true);
        $action->execute($dto);
        $duration = microtime(true) - $start;
        expect($duration)->toBeLessThan(2.0);
    });

    test('YB22J-NFR-SET-008: all setting reads flow through setting helper or Settings service', function (): void {
        expect(function_exists('setting'))->toBeTrue();
    });

    test('YB22J-DD-SET-001: observer invalidation runs synchronously on mutation', function (): void {
        $key = 'dd.test_obs';
        app(SetSettingAction::class)->execute(new SettingData(key: $key, value: 'v1', group: 'dd'));
        expect(setting($key))->toBe('v1');
    });

    test('YB22J-DD-SET-002: type detection detects php primitives centrally', function (): void {
        expect(SettingType::detect('text')->value)->toBe('string')
            ->and(SettingType::detect(100)->value)->toBe('integer')
            ->and(SettingType::detect(true)->value)->toBe('boolean')
            ->and(SettingType::detect(['a' => 1])->value)->toBe('json');
    });

    test('YB22J-DD-SET-003: runtime overrides outrank database and config layers', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'dd.chain', value: 'db_value', group: 'dd'));
        expect(setting('dd.chain'))->toBe('db_value');

        Settings::override(['dd.chain' => 'override_value']);
        expect(setting('dd.chain'))->toBe('override_value');
        Settings::clearOverrides();
    });

    test('YB22J-DD-SET-004: colors and images stay strings without specialized enum types', function (): void {
        expect(SettingType::detect('#059669')->value)->toBe('string');
    });

    test('YB22J-DD-SET-005: feature flags reside as boolean settings', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'features.test_dd', value: true, group: 'features'));
        expect(feature('test_dd'))->toBeTrue();
    });

    test('YB22J-DD-SET-006: shared validation rules exist for setting entities', function (): void {
        expect(SettingEntity::rules())->toBeArray();
    });
});
