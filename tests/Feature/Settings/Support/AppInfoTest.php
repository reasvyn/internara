<?php

declare(strict_types=1);

use App\Domain\Core\Support\CacheKeys;
use App\Domain\Settings\Support\AppInfo;
use Illuminate\Support\Facades\Cache;

describe('AppInfo', function () {
    beforeEach(function () {
        AppInfo::clearCache();
    });

    it('reads composer.json metadata', function () {
        $info = AppInfo::all();

        expect($info)->toHaveKeys(['name', 'version', 'description', 'license', 'author', 'support']);
        expect($info['name'])->toBe('Internara');
        expect($info['version'])->toBe('0.1.0');
        expect($info['license'])->toBe('MIT');
    });

    it('gets specific metadata by key', function () {
        expect(AppInfo::get('name'))->toBe('Internara');
        expect(AppInfo::get('version'))->toBe('0.1.0');
        expect(AppInfo::get('author.name'))->toBe('Reas Vyn');
        expect(AppInfo::get('author.github'))->toContain('github.com/reasvyn');
    });

    it('returns default when key not found', function () {
        expect(AppInfo::get('nonexistent', 'fallback'))->toBe('fallback');
        expect(AppInfo::get('nonexistent'))->toBeNull();
    });

    it('returns version string', function () {
        expect(AppInfo::version())->toBe('0.1.0');
    });

    it('returns author array', function () {
        $author = AppInfo::author();

        expect($author)->toHaveKeys(['name', 'email', 'homepage']);
        expect($author['name'])->toBe('Reas Vyn');
    });

    it('caches metadata', function () {
        AppInfo::all();

        expect(Cache::has(CacheKeys::APPINFO_METADATA))->toBeTrue();
    });

    it('clearCache resets both static and cache', function () {
        AppInfo::all();
        expect(Cache::has(CacheKeys::APPINFO_METADATA))->toBeTrue();

        AppInfo::clearCache();

        expect(Cache::has(CacheKeys::APPINFO_METADATA))->toBeFalse();
    });
});
