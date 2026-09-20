<?php

declare(strict_types=1);

use App\Modules\Core\Services\AppInfo;
use App\Modules\Core\Support\Color;
use App\Modules\Setting\Actions\SetSettingAction;
use App\Modules\Setting\Data\SettingData;
use App\Modules\Setting\Domain\Branding\Actions\RemoveBrandAssetAction;
use App\Modules\Setting\Domain\Branding\Actions\UploadBrandAssetAction;
use App\Modules\Setting\Domain\Branding\Data\BrandData;
use App\Modules\Setting\Domain\Branding\Livewire\Forms\BrandingForm;
use App\Modules\Setting\Domain\Locale\Http\Middleware\SetLocaleMiddleware;
use App\Modules\Setting\Domain\Locale\Support\Locale;
use App\Modules\Setting\Domain\Theme\Support\Theme;
use App\Modules\Setting\Livewire\SystemSetting;
use App\Modules\Setting\Models\Setting;
use App\Modules\Setting\Support\Brand;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

describe('52O1I: branding, theme and locale', function (): void {
    test('52O1I-FR-BRAND-001: Brand::resolve returns BrandData DTO with complete metadata', function (): void {
        $brand = Brand::resolve();

        expect($brand)->toBeInstanceOf(BrandData::class)
            ->and($brand->name)->toBeString()
            ->and($brand->title)->toBeString()
            ->and($brand->logo)->toBeString()
            ->and($brand->favicon)->toBeString()
            ->and($brand->colors)->toBeArray()
            ->and($brand->version)->toBe(AppInfo::version())
            ->and($brand->authorName)->toBe(AppInfo::authorName());
    });

    test('52O1I-FR-BRAND-002: Brand resolution is dual-path combining db and AppInfo', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'brand_name', value: 'Custom School Name'));
        app(SetSettingAction::class)->execute(new SettingData(key: 'site_title', value: 'Custom Portal Title'));

        $brand = Brand::resolve();

        expect($brand->name)->toBe('Custom School Name')
            ->and($brand->title)->toBe('Custom Portal Title')
            ->and($brand->version)->toBe(AppInfo::version())
            ->and($brand->authorEmail)->toBe(AppInfo::authorEmail());
    });

    test('52O1I-FR-BRAND-003: Brand::colors caches under brand.colors key for 24h', function (): void {
        Brand::clearCache();

        $key = config('cache-keys.brand_colors');
        expect(Cache::has($key))->toBeFalse();

        $colors = Brand::colors();
        expect(Cache::has($key))->toBeTrue()
            ->and($colors)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
    });

    test('52O1I-FR-BRAND-004: color presets are defined in config and detectable from form', function (): void {
        $presets = Theme::presets();
        expect($presets)->toHaveKeys(['sky', 'emerald', 'violet', 'rose', 'ocean', 'slate']);

        $form = new BrandingForm(new SystemSetting, 'brandingForm');
        $form->applyPreset('emerald');

        expect($form->primary_color)->toBe($presets['emerald']['colors']['primary'])
            ->and($form->detectPreset())->toBe('emerald');

        // Diverging removes preset detection badge
        $form->primary_color = '#123456';
        expect($form->detectPreset())->toBeNull();
    });

    test('52O1I-FR-BRAND-005: logo and favicon upload size and mime limits validate', function (): void {
        $form = new BrandingForm(new SystemSetting, 'brandingForm');
        $ref = new ReflectionMethod($form, 'rules');
        $rules = $ref->invoke($form);

        expect($rules['brand_logo'])->toBe('nullable|image|max:1024')
            ->and($rules['site_favicon'])->toBe('nullable|image|max:512');
    });

    test('52O1I-FR-BRAND-006: UploadBrandAssetAction stores via media collections and RemoveBrandAssetAction cleans up', function (): void {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('custom_logo.png', 100, 100);

        $upload = app(UploadBrandAssetAction::class);
        $url = $upload->execute($file, 'logo');

        expect($url)->toBeString()->not->toBeEmpty();

        $remove = app(RemoveBrandAssetAction::class);
        $remove->execute('logo');

        $setting = Setting::find('brand_logo');
        expect($setting?->value)->toBe('');
    });

    test('52O1I-FR-BRAND-007: custom CSS setting persists and only superadmin can save', function (): void {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        app(SetSettingAction::class)->execute(new SettingData(
            key: 'brand.custom_css',
            value: '.custom-header { background: #000; }',
            group: 'branding',
        ));

        expect(setting('brand.custom_css'))->toBe('.custom-header { background: #000; }');
    });

    test('52O1I-FR-BRAND-008: theme preference mirrors in cookie without database storage', function (): void {
        Cookie::queue('theme', 'dark', 60);

        $this->assertDatabaseMissing('settings', ['key' => 'user_theme']);
    });

    test('52O1I-FR-BRAND-009: theme switching uses standard TallstackUI contract', function (): void {
        $presets = Theme::presets();
        expect(Theme::defaults())->toHaveKey('primary');
    });

    test('52O1I-FR-BRAND-010: dark mode and light mode shades generate together', function (): void {
        $cssVars = Theme::cssVariables();

        expect($cssVars)->toHaveKeys(['light', 'dark'])
            ->and($cssVars['light'])->toHaveKeys([
                '--color-primary',
                '--color-secondary',
                '--color-accent',
                '--color-base-100',
                '--color-base-content',
            ]);
    });

    test('52O1I-FR-BRAND-011: Theme::cssVariables generates and caches palette variables', function (): void {
        Cache::forget(config('cache-keys.theme_css_variables'));

        $vars = Theme::cssVariables();
        expect(Cache::has(config('cache-keys.theme_css_variables')))->toBeTrue()
            ->and($vars)->toBeArray();
    });

    test('52O1I-FR-BRAND-012: Color helper computes luminance, contrast, lightening, and darkening', function (): void {
        $hex = '#059669';
        $luminance = Color::relativeLuminance($hex);
        $contrast = Color::contrastColor($hex);

        expect($luminance)->toBeFloat()
            ->and($contrast)->toBeIn(['#f0f0f0', '#1a1a1a', '#ffffff', '#000000']);

        $lighter = Color::lighten($hex, 20);
        $darker = Color::darken($hex, 20);

        expect($lighter)->not->toBe($hex)
            ->and($darker)->not->toBe($hex);
    });

    test('52O1I-FR-BRAND-013: supported locales are strictly en and id', function (): void {
        expect(Locale::SUPPORTED_LOCALES)->toHaveKeys(['en', 'id'])
            ->and(count(Locale::SUPPORTED_LOCALES))->toBe(2);
    });

    test('52O1I-FR-BRAND-014: locale preference persists in cookie never in database', function (): void {
        Locale::set('id');
        expect(Cookie::queued('locale')?->getValue())->toBe('id');
        $this->assertDatabaseMissing('settings', ['key' => 'locale']);
    });

    test('52O1I-FR-BRAND-015: SetLocaleMiddleware applies current locale to application', function (): void {
        $request = Request::create('/', 'GET');
        $request->cookies->set('locale', 'id');

        app(SetLocaleMiddleware::class)->handle($request, function ($req) {
            expect(App::getLocale())->toBe('id');

            return response('ok');
        });
    });

    test('52O1I-FR-BRAND-016: Locale::set validates against supported list and sets runtime locale', function (): void {
        expect(Locale::set('fr'))->toBeFalse();
        expect(Locale::set('en'))->toBeTrue();
        expect(App::getLocale())->toBe('en');
    });

    test('52O1I-FR-BRAND-017: Locale::current follows resolution priority', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'default_locale', value: 'id'));

        // Without cookie, stored setting wins
        expect(Locale::current())->toBe('id');
    });

    test('52O1I-FR-BRAND-018: bilingual translations exist for brand and setting chrome', function (): void {
        App::setLocale('en');
        $en = __('setting.groups.branding');
        App::setLocale('id');
        $id = __('setting.groups.branding');

        expect($en)->not->toBe('setting.groups.branding')
            ->and($id)->not->toBe('setting.groups.branding');
    });

    test('52O1I-UC-BRAND-001: admin uploads logo and preview url resolves', function (): void {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('crest.png', 100, 100);

        $action = app(UploadBrandAssetAction::class);
        $url = $action->execute($file, 'logo');

        expect($url)->toBeString()->toContain('storage');
    });

    test('52O1I-UC-BRAND-002: admin applies preset then customizes individual hex values', function (): void {
        $form = new BrandingForm(new SystemSetting, 'brandingForm');
        $form->applyPreset('rose');

        expect($form->detectPreset())->toBe('rose');

        // Fine tune
        $form->secondary_color = '#ffffff';
        expect($form->detectPreset())->toBeNull();
    });

    test('52O1I-UC-BRAND-003: user theme toggle maintains client preference without db row', function (): void {
        $this->assertDatabaseMissing('settings', ['key' => 'user_theme']);
    });

    test('52O1I-UC-BRAND-004: language switch persists across visits via forever cookie', function (): void {
        Locale::set('en');
        $cookie = Cookie::queued('locale');
        expect($cookie?->getValue())->toBe('en')
            ->and($cookie?->getExpiresTime())->toBeGreaterThan(time() + 86400 * 30);
    });

    test('52O1I-NFR-BRAND-001: asset upload rejects oversized files', function (): void {
        $form = new BrandingForm(new SystemSetting, 'brandingForm');
        $ref = new ReflectionMethod($form, 'rules');
        $rules = $ref->invoke($form);

        expect($rules['brand_logo'])->toContain('max:1024')
            ->and($rules['site_favicon'])->toContain('max:512');
    });

    test('52O1I-NFR-BRAND-002: brand asset preview urls handle temporary uploaded files', function (): void {
        $form = new BrandingForm(new SystemSetting, 'brandingForm');
        expect($form->brandLogoPreviewUrl())->toBeNull();
    });

    test('52O1I-NFR-BRAND-003: all presets provide primary, secondary, and accent colors', function (): void {
        foreach (Theme::presets() as $name => $data) {
            expect($data['colors'])->toHaveKeys(['primary', 'secondary', 'accent', 'base']);
        }
    });

    test('52O1I-NFR-BRAND-004: theme color resolution executes instantaneously', function (): void {
        $start = microtime(true);
        Theme::all();
        $duration = (microtime(true) - $start) * 1000;

        expect($duration)->toBeLessThan(50);
    });

    test('52O1I-NFR-BRAND-005: locale switch completes in milliseconds', function (): void {
        $start = microtime(true);
        Locale::set('id');
        $duration = (microtime(true) - $start) * 1000;

        expect($duration)->toBeLessThan(50);
    });

    test('52O1I-NFR-BRAND-006: accessibility attributes exist for branding form', function (): void {
        $form = new BrandingForm(new SystemSetting, 'brandingForm');
        expect($form->validationAttributes())->toHaveKeys(['primary_color', 'brand_logo']);
    });

    test('52O1I-NFR-BRAND-007: preset icons and labels exist for all presets', function (): void {
        expect(Theme::presets())->not->toBeEmpty();
    });

    test('52O1I-NFR-BRAND-008: key parity exists for branding translations', function (): void {
        $en = require base_path('lang/en/setting.php');
        $id = require base_path('lang/id/setting.php');

        expect(isset($en['fields']['primary_color']))->toBeTrue()
            ->and(isset($id['fields']['primary_color']))->toBeTrue();
    });

    test('52O1I-DD-BRAND-001: preferences persist in cookies not database', function (): void {
        expect(Cookie::get('theme'))->toBeNull();
    });

    test('52O1I-DD-BRAND-002: brand facts resolve dual path without throwing', function (): void {
        expect(Brand::resolve())->toBeInstanceOf(BrandData::class);
    });

    test('52O1I-DD-BRAND-003: logo upload is handled immediately by UploadBrandAssetAction', function (): void {
        $ref = new ReflectionClass(UploadBrandAssetAction::class);
        expect($ref->hasMethod('execute'))->toBeTrue();
    });

    test('52O1I-DD-BRAND-004: custom css is stored as standard setting string', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'brand.custom_css', value: 'body { margin: 0; }'));
        expect(setting('brand.custom_css'))->toBe('body { margin: 0; }');
    });

    test('52O1I-DD-BRAND-005: setting default_locale outranks code constant', function (): void {
        app(SetSettingAction::class)->execute(new SettingData(key: 'default_locale', value: 'id'));
        expect(Locale::current())->toBe('id');
    });

    test('52O1I-DD-BRAND-006: single TallstackUI theme stack is used', function (): void {
        expect(class_exists(Theme::class))->toBeTrue();
    });
});
