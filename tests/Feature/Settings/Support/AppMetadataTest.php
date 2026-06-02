<?php

declare(strict_types=1);

use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Support\AppInfo;
use App\Domain\Settings\Support\AppMetadata;
use App\Domain\Settings\Support\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    AppInfo::clearCache();
    Settings::clearOverrides();
    Cache::flush();
});

describe('AppMetadata', function () {
    describe('appName', function () {
        it('returns name from AppInfo', function () {
            expect(AppMetadata::appName())->toBe('Internara');
        });
    });

    describe('brandName', function () {
        it('returns brand name from settings when installed', function () {
            Setting::create(['key' => 'brand_name', 'value' => 'My Institution', 'type' => 'string']);

            expect(AppMetadata::brandName())->toBe('My Institution');
        });

        it('falls back to app name when not set', function () {
            expect(AppMetadata::brandName())->toBe('Internara');
        });
    });

    describe('siteTitle', function () {
        it('returns site title from settings', function () {
            Setting::create(['key' => 'site_title', 'value' => 'My Site', 'type' => 'string']);

            expect(AppMetadata::siteTitle())->toBe('My Site');
        });

        it('falls back to brand name', function () {
            Setting::create(['key' => 'brand_name', 'value' => 'Brand', 'type' => 'string']);

            expect(AppMetadata::siteTitle())->toBe('Brand');
        });
    });

    describe('brandLogo', function () {
        it('returns brand logo from settings', function () {
            Setting::create(['key' => 'brand_logo', 'value' => '/storage/logo.png', 'type' => 'string']);

            expect(AppMetadata::brandLogo())->toBe('/storage/logo.png');
        });

        it('falls back to default asset path', function () {
            expect(AppMetadata::brandLogo())->toMatch('/^http.*\/brand\/logo\.png$/');
        });
    });

    describe('favicon', function () {
        it('returns favicon from settings', function () {
            Setting::create(['key' => 'site_favicon', 'value' => '/storage/favicon.ico', 'type' => 'string']);

            expect(AppMetadata::favicon())->toBe('/storage/favicon.ico');
        });

        it('falls back to brand logo', function () {
            Setting::create(['key' => 'brand_logo', 'value' => '/storage/logo.png', 'type' => 'string']);

            expect(AppMetadata::favicon())->toBe('/storage/logo.png');
        });

        it('falls back to default favicon', function () {
            expect(AppMetadata::favicon())->toMatch('/^http.*\/brand\/favicon\.ico$/');
        });
    });

    describe('colors', function () {
        it('returns theme defaults', function () {
            $colors = AppMetadata::colors();

            expect($colors)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
        });
    });

    describe('version', function () {
        it('returns version from AppInfo', function () {
            expect(AppMetadata::version())->toBe('0.1.0');
        });
    });

    describe('authorName', function () {
        it('returns author name from AppInfo', function () {
            expect(AppMetadata::authorName())->toBe('Reas Vyn');
        });
    });

    describe('authorEmail', function () {
        it('returns author email from AppInfo', function () {
            expect(AppMetadata::authorEmail())->toBeString();
        });
    });

    describe('description', function () {
        it('returns description from AppInfo', function () {
            expect(AppMetadata::description())->toBeString();
            expect(AppMetadata::description())->not->toBeEmpty();
        });
    });

    describe('license', function () {
        it('returns license from AppInfo', function () {
            expect(AppMetadata::license())->toBe('MIT');
        });
    });

    describe('get', function () {
        it('routes known keys to correct methods', function () {
            expect(AppMetadata::get('app_name'))->toBe('Internara');
            expect(AppMetadata::get('version'))->toBe('0.1.0');
            expect(AppMetadata::get('license'))->toBe('MIT');
        });

        it('routes name to brandName', function () {
            Setting::create(['key' => 'brand_name', 'value' => 'Custom', 'type' => 'string']);

            expect(AppMetadata::get('name'))->toBe('Custom');
        });

        it('falls back to AppInfo for unknown keys', function () {
            expect(AppMetadata::get('description'))->toBeString();
        });
    });
});
