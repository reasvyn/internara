<?php

declare(strict_types=1);

use App\Domain\Settings\Support\AppInfo;

describe('AppInfo', function () {
    beforeEach(function () {
        AppInfo::clearCache();
    });

    it('has name from composer.json', function () {
        $name = AppInfo::get('name');

        expect($name)->toBeString()
            ->and($name)->not->toBeEmpty();
    });

    it('has version from composer.json', function () {
        $version = AppInfo::version();

        expect($version)->toBeString();
    });

    it('has author information', function () {
        $author = AppInfo::author();

        expect($author)->toBeArray();
    });

    it('returns all metadata', function () {
        $all = AppInfo::all();

        expect($all)->toHaveKeys(['name', 'version', 'description', 'license', 'author', 'support']);
    });

    it('returns default when key not found', function () {
        $result = AppInfo::get('nonexistent', 'fallback');

        expect($result)->toBe('fallback');
    });

    it('returns default logo path', function () {
        $logo = AppInfo::logo();

        expect($logo)->toBeString();
    });

    it('is a final class', function () {
        $ref = new ReflectionClass(AppInfo::class);

        expect($ref->isFinal())->toBeTrue();
    });
});
