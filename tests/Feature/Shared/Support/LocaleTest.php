<?php

declare(strict_types=1);

use App\Domain\Shared\Support\Locale;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

describe('Locale', function () {
    describe('set', function () {
        it('sets supported locale and queues cookie', function () {
            $result = Locale::set('id');

            expect($result)->toBeTrue();
            expect(App::getLocale())->toBe('id');
            expect(Cookie::hasQueued('locale'))->toBeTrue();
        });

        it('returns false for unsupported locale', function () {
            $result = Locale::set('fr');

            expect($result)->toBeFalse();
            expect(App::getLocale())->toBe('id');
        });
    });

    describe('current', function () {
        it('returns locale from config when no cookie', function () {
            config(['app.locale' => 'en']);

            expect(Locale::current())->toBe('en');
        });

        it('returns default when no locale source', function () {
            expect(Locale::DEFAULT_LOCALE)->toBe('en');
        });
    });

    describe('all', function () {
        it('returns all supported locales with metadata', function () {
            $locales = Locale::all();

            expect($locales)->toHaveKeys(['en', 'id']);
            expect($locales['en']['name'])->toBe('English');
            expect($locales['id']['name'])->toBe('Indonesian');
        });
    });

    describe('keys', function () {
        it('returns locale codes array', function () {
            $keys = Locale::keys();

            expect($keys)->toBe(['en', 'id']);
        });
    });

    describe('isSupported', function () {
        it('returns true for en and id', function () {
            expect(Locale::isSupported('en'))->toBeTrue();
            expect(Locale::isSupported('id'))->toBeTrue();
        });

        it('returns false for unsupported locales', function () {
            expect(Locale::isSupported('fr'))->toBeFalse();
            expect(Locale::isSupported('de'))->toBeFalse();
            expect(Locale::isSupported(''))->toBeFalse();
        });
    });

    describe('metadata', function () {
        it('returns metadata for supported locale', function () {
            $meta = Locale::metadata('en');

            expect($meta)->toBe(['name' => 'English', 'native' => 'English']);
        });

        it('returns null for unsupported locale', function () {
            expect(Locale::metadata('fr'))->toBeNull();
        });
    });
});
