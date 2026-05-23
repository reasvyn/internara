<?php

declare(strict_types=1);

use App\Domain\Settings\Support\AppMetadata;

describe('AppMetadata', function () {
    it('returns app name from composer', function () {
        $name = AppMetadata::appName();

        expect($name)->toBeString();
    });

    it('returns version from composer', function () {
        $version = AppMetadata::version();

        expect($version)->toBeString();
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
            ->toContain('/brand/logo.png');
    });

    it('returns a value by key', function () {
        $result = AppMetadata::get('name');

        expect($result)->toBeString();
    });

    it('returns default for unknown key', function () {
        $result = AppMetadata::get('unknown_key', 'fallback');

        expect($result)->toBe('fallback');
    });
});
