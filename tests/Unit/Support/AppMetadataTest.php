<?php

declare(strict_types=1);

use App\Models\Setup;
use App\Support\AppInfo;
use App\Support\AppMetadata;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    AppInfo::clearCache();
    Settings::clearOverrides();
    Cache::clear();
});

afterEach(function () {
    Setup::query()->delete();
});

describe('appName', function () {
    it('returns app name from Composer SSoT', function () {
        $name = AppMetadata::appName();

        expect($name)->toBe('Internara');
    });
});

describe('brandName', function () {
    it('returns app name when not installed', function () {
        $brandName = AppMetadata::brandName();

        expect($brandName)->toBe(AppInfo::get('name'));
    });
});

describe('siteTitle', function () {
    it('returns string', function () {
        $title = AppMetadata::siteTitle();

        expect($title)->toBeString();
    });
});

describe('appLogo', function () {
    it('returns URL containing logo.png', function () {
        $logo = AppMetadata::appLogo();

        expect($logo)->toBeString();
        expect($logo)->toContain('logo.png');
    });
});

describe('version', function () {
    it('returns version from composer.json', function () {
        $version = AppMetadata::version();

        expect($version)->toBe('0.1.0');
    });
});

describe('authorName', function () {
    it('returns author name from composer.json', function () {
        $authorName = AppMetadata::authorName();

        expect($authorName)->toBe('Reas Vyn');
    });
});

describe('authorEmail', function () {
    it('returns author email from composer.json', function () {
        $email = AppMetadata::authorEmail();

        expect($email)->toBe('reasvyn@gmail.com');
    });
});

describe('license', function () {
    it('returns license from composer.json', function () {
        $license = AppMetadata::license();

        expect($license)->toBe('MIT');
    });
});

describe('description', function () {
    it('returns description from composer.json', function () {
        $description = AppMetadata::description();

        expect($description)->toContain('field work management system');
    });
});

describe('colors', function () {
    it('returns default colors when no overrides', function () {
        $colors = AppMetadata::colors();

        expect($colors)->toBeArray();
        expect($colors)->toHaveKeys(['primary', 'secondary', 'accent']);
        expect($colors['primary'])->toBe('#0ea5e9');
        expect($colors['secondary'])->toBe('#64748b');
        expect($colors['accent'])->toBe('#f59e0b');
    });
});

describe('get method', function () {
    it('returns mapped values by key', function () {
        expect(AppMetadata::get('name'))->toBeString();
        expect(AppMetadata::get('app_name'))->toBeString();
        expect(AppMetadata::get('logo'))->toBeString();
        expect(AppMetadata::get('app_logo'))->toContain('logo.png');
        expect(AppMetadata::get('favicon'))->toBeString();
        expect(AppMetadata::get('site_title'))->toBeString();
        expect(AppMetadata::get('version'))->toBeString();
        expect(AppMetadata::get('author_name'))->toBeString();
        expect(AppMetadata::get('author_email'))->toBeString();
        expect(AppMetadata::get('description'))->toBeString();
        expect(AppMetadata::get('license'))->toBeString();
        expect(AppMetadata::get('colors'))->toBeArray();
    });

    it('returns default for unknown key', function () {
        expect(AppMetadata::get('non_existent', 'default'))->toBe('default');
    });

    it('falls through to AppInfo for unmapped keys', function () {
        expect(AppMetadata::get('support'))->toBe(AppInfo::get('support'));
    });
});
