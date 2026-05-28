<?php

declare(strict_types=1);

use App\Domain\Shared\Support\Locale;

describe('Locale', function () {
    beforeEach(function () {
        \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget('locale'));
    });

    it('has default locale set to en', function () {
        expect(Locale::DEFAULT_LOCALE)->toBe('en');
    });

    it('supports id and en locales', function () {
        expect(Locale::keys())->toContain('id', 'en');
    });

    it('sets and retrieves locale', function () {
        $result = Locale::set('en');

        expect($result)->toBeTrue()
            ->and(Locale::current())->toBe('en');
    });

    it('returns false for unsupported locale', function () {
        expect(Locale::set('fr'))->toBeFalse();
    });

    it('returns current locale from cookie', function () {
        Locale::set('id');

        expect(Locale::current())->toBe('id');
    });

    it('returns all supported locales', function () {
        $all = Locale::all();

        expect($all)->toHaveKeys(['en', 'id']);
    });

    it('checks if locale is supported', function () {
        expect(Locale::isSupported('en'))->toBeTrue()
            ->and(Locale::isSupported('fr'))->toBeFalse();
    });

    it('returns metadata for supported locale', function () {
        $meta = Locale::metadata('en');

        expect($meta)->toHaveKey('name')
            ->and($meta)->toHaveKey('native');
    });

    it('returns null for unsupported locale metadata', function () {
        expect(Locale::metadata('fr'))->toBeNull();
    });

    it('is a final class', function () {
        $ref = new ReflectionClass(Locale::class);

        expect($ref->isFinal())->toBeTrue();
    });
});
