<?php

declare(strict_types=1);

use App\Domain\Settings\Support\AppMetadata;

describe('AppMetadata', function () {
    it('returns app name from composer', function () {
        $name = AppMetadata::appName();

        expect($name)->toBeString()
            ->not->toBeEmpty();
    });

    it('returns version from composer', function () {
        $version = AppMetadata::version();

        expect($version)->toBeString()
            ->not->toBeEmpty();
    });

    it('returns author name', function () {
        $author = AppMetadata::authorName();

        expect($author)->toBeString();
    });

    it('returns author email', function () {
        $email = AppMetadata::authorEmail();

        expect($email)->toBeString();
    });

    it('returns description', function () {
        $desc = AppMetadata::description();

        expect($desc)->toBeString();
    });

    it('returns license', function () {
        $license = AppMetadata::license();

        expect($license)->toBeString();
    });

    it('returns app logo url', function () {
        $logo = AppMetadata::appLogo();

        expect($logo)->toBeString()
            ->toEndWith('/brand/logo.png');
    });

    it('returns brand name as fallback when not installed', function () {
        $name = AppMetadata::brandName();

        expect($name)->toBeString()
            ->not->toBeEmpty();
    });

    it('returns site title as fallback when not installed', function () {
        $title = AppMetadata::siteTitle();

        expect($title)->toBeString()
            ->not->toBeEmpty();
    });

    it('returns default brand logo when not installed', function () {
        $logo = AppMetadata::brandLogo();

        expect($logo)->toBeString()
            ->toEndWith('/brand/logo.png');
    });

    it('returns default favicon when not installed', function () {
        $favicon = AppMetadata::favicon();

        expect($favicon)->toBeString()
            ->toContain('/brand/');
    });

    it('returns colors as array with default keys', function () {
        $colors = AppMetadata::colors();

        expect($colors)->toBeArray()
            ->toHaveKeys(['primary', 'secondary', 'accent', 'base']);
    });

    it('returns a value by key', function () {
        $result = AppMetadata::get('name');

        expect($result)->toBeString()
            ->not->toBeEmpty();
    });

    it('resolves known keys via get mapping', function () {
        expect(AppMetadata::get('app_name'))->toBeString();
        expect(AppMetadata::get('logo'))->toEndWith('/brand/logo.png');
        expect(AppMetadata::get('version'))->toBeString();
        expect(AppMetadata::get('author_name'))->toBeString();
        expect(AppMetadata::get('license'))->toBeString();
    });

    it('returns default for unknown key', function () {
        $result = AppMetadata::get('unknown_key', 'fallback');

        expect($result)->toBe('fallback');
    });
});
