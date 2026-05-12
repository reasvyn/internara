<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Setup;
use App\Support\AppInfo;
use App\Support\AppMetadata;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Settings::clearOverrides();
    Cache::clear();
    AppInfo::clearCache();
    Setup::query()->delete();
});

afterEach(function () {
    Setup::query()->delete();
});

describe('brand name resolution', function () {
    it('returns app name from composer when not installed', function () {
        $name = AppMetadata::brandName();

        expect($name)->toBe(AppInfo::get('name'));
    });

    it('returns brand_name from settings when installed', function () {
        Setup::factory()->installed()->create();
        Setting::factory()->create(['key' => 'brand_name', 'value' => 'My Institution']);

        $name = AppMetadata::brandName();

        expect($name)->toBe('My Institution');
    });

    it('falls back to app name when brand_name setting is missing', function () {
        Setup::factory()->installed()->create();

        $name = AppMetadata::brandName();

        expect($name)->toBe(AppInfo::get('name'));
    });
});

describe('site title resolution', function () {
    it('returns app name with Setup suffix when not installed', function () {
        $title = AppMetadata::siteTitle();

        expect($title)->toBe(AppInfo::get('name').' - Setup');
    });

    it('returns site_title from settings when installed', function () {
        Setup::factory()->installed()->create();
        Setting::factory()->create(['key' => 'site_title', 'value' => 'My Site']);

        $title = AppMetadata::siteTitle();

        expect($title)->toBe('My Site');
    });

    it('falls back to brand name when site_title setting is missing', function () {
        Setup::factory()->installed()->create();
        Setting::factory()->create(['key' => 'brand_name', 'value' => 'Institution Name']);

        $title = AppMetadata::siteTitle();

        expect($title)->toBe('Institution Name');
    });
});

describe('brand logo resolution', function () {
    it('returns default logo URL when not installed', function () {
        $logo = AppMetadata::brandLogo();

        expect($logo)->toContain('logo.png');
    });

    it('returns custom logo from settings when installed', function () {
        Setup::factory()->installed()->create();
        Setting::factory()->create(['key' => 'brand_logo', 'value' => '/storage/logo.png']);

        $logo = AppMetadata::brandLogo();

        expect($logo)->toBe('/storage/logo.png');
    });

    it('falls back to default logo when brand_logo is empty string', function () {
        Setup::factory()->installed()->create();
        Setting::factory()->create(['key' => 'brand_logo', 'value' => '']);

        $logo = AppMetadata::brandLogo();

        expect($logo)->toContain('logo.png');
    });
});

describe('favicon resolution', function () {
    it('returns default favicon URL when not installed', function () {
        $favicon = AppMetadata::favicon();

        expect($favicon)->toContain('favicon.ico');
    });

    it('returns site_favicon from settings when installed', function () {
        Setup::factory()->installed()->create();
        Setting::factory()->create(['key' => 'site_favicon', 'value' => '/storage/favicon.ico']);

        $favicon = AppMetadata::favicon();

        expect($favicon)->toBe('/storage/favicon.ico');
    });

    it('falls back to brand_logo when site_favicon is missing', function () {
        Setup::factory()->installed()->create();
        Setting::factory()->create(['key' => 'brand_logo', 'value' => '/storage/logo.png']);

        $favicon = AppMetadata::favicon();

        expect($favicon)->toBe('/storage/logo.png');
    });
});

describe('brand colors resolution', function () {
    it('returns default colors when no settings exist', function () {
        $colors = AppMetadata::colors();

        expect($colors)->toBe([
            'primary' => '#0ea5e9',
            'secondary' => '#64748b',
            'accent' => '#f59e0b',
        ]);
    });

    it('uses settings values when available', function () {
        Setting::factory()->create(['key' => 'primary_color', 'value' => '#ff0000']);
        Setting::factory()->create(['key' => 'secondary_color', 'value' => '#00ff00']);
        Setting::factory()->create(['key' => 'accent_color', 'value' => '#0000ff']);

        $colors = AppMetadata::colors();

        expect($colors)->toBe([
            'primary' => '#ff0000',
            'secondary' => '#00ff00',
            'accent' => '#0000ff',
        ]);
    });

    it('merges partial settings with defaults', function () {
        Setting::factory()->create(['key' => 'primary_color', 'value' => '#ff0000']);

        $colors = AppMetadata::colors();

        expect($colors['primary'])->toBe('#ff0000');
        expect($colors['secondary'])->toBe('#64748b');
        expect($colors['accent'])->toBe('#f59e0b');
    });
});

describe('app name vs brand name', function () {
    it('app_name always returns composer name', function () {
        Setup::factory()->installed()->create();
        Setting::factory()->create(['key' => 'brand_name', 'value' => 'Custom Brand']);

        $appName = AppMetadata::appName();
        $brandName = AppMetadata::brandName();

        expect($appName)->toBe(AppInfo::get('name'));
        expect($brandName)->toBe('Custom Brand');
        expect($appName)->not->toBe($brandName);
    });
});

describe('get method mapping', function () {
    it('maps name to brandName', function () {
        expect(AppMetadata::get('name'))->toBe(AppMetadata::brandName());
    });

    it('maps app_name to appName', function () {
        expect(AppMetadata::get('app_name'))->toBe(AppMetadata::appName());
    });

    it('maps logo to brandLogo', function () {
        expect(AppMetadata::get('logo'))->toBe(AppMetadata::brandLogo());
    });

    it('maps app_logo to appLogo', function () {
        expect(AppMetadata::get('app_logo'))->toBe(AppMetadata::appLogo());
    });

    it('maps favicon to favicon', function () {
        expect(AppMetadata::get('favicon'))->toBe(AppMetadata::favicon());
    });

    it('maps site_title to siteTitle', function () {
        expect(AppMetadata::get('site_title'))->toBe(AppMetadata::siteTitle());
    });

    it('maps colors to colors', function () {
        expect(AppMetadata::get('colors'))->toBe(AppMetadata::colors());
    });

    it('maps version to version', function () {
        expect(AppMetadata::get('version'))->toBe(AppMetadata::version());
    });

    it('maps author_name to authorName', function () {
        expect(AppMetadata::get('author_name'))->toBe(AppMetadata::authorName());
    });

    it('maps author_email to authorEmail', function () {
        expect(AppMetadata::get('author_email'))->toBe(AppMetadata::authorEmail());
    });

    it('maps description to description', function () {
        expect(AppMetadata::get('description'))->toBe(AppMetadata::description());
    });

    it('maps license to license', function () {
        expect(AppMetadata::get('license'))->toBe(AppMetadata::license());
    });

    it('falls through to AppInfo for unmapped keys', function () {
        expect(AppMetadata::get('support'))->toBe(AppInfo::get('support'));
    });

    it('returns default for unknown key', function () {
        expect(AppMetadata::get('ghost', 'fallback'))->toBe('fallback');
    });
});

describe('brand() helper integration', function () {
    it('brand helper returns same values as AppMetadata', function () {
        expect(brand('name'))->toBe(AppMetadata::brandName());
        expect(brand('app_name'))->toBe(AppMetadata::appName());
        expect(brand('version'))->toBe(AppMetadata::version());
        expect(brand('colors'))->toBe(AppMetadata::colors());
    });

    it('brand helper returns default for unknown key', function () {
        expect(brand('ghost', 'fallback'))->toBe('fallback');
    });
});
