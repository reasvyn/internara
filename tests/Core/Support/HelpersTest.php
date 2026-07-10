<?php

declare(strict_types=1);

namespace Tests\Core\Support;

use App\Core\Services\AppInfo;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

describe('app_info() helper', function () {
    beforeEach(function () {
        AppInfo::clearCache();
        Cache::flush();
    });

    it('returns all metadata when called without key', function () {
        $result = app_info();

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['name', 'version', 'description', 'license', 'author', 'support']);
    });

    it('returns specific metadata by key', function () {
        $result = app_info('name');

        expect($result)->toBeString();
    });

    it('returns version', function () {
        $result = app_info('version');

        expect($result)->toBeString();
    });

    it('returns author array', function () {
        $result = app_info('author');

        expect($result)->toBeArray();
    });

    it('returns default for unknown key', function () {
        $result = app_info('unknown_key', 'default_value');

        expect($result)->toBe('default_value');
    });

    it('returns null for unknown key without default', function () {
        $result = app_info('unknown_key');

        expect($result)->toBeNull();
    });
});
