<?php

declare(strict_types=1);

use App\Modules\Settings\Enums\MediaCollection;

describe('52O1I: MediaCollection enum', function (): void {
    test('52O1I-FR-BRAND-006: cases carry the specified backing values', function (): void {
        expect(MediaCollection::LOGO->value)->toBe('brand_logo');
        expect(MediaCollection::from('brand_logo'))->toBe(MediaCollection::LOGO);
        expect(MediaCollection::FAVICON->value)->toBe('brand_favicon');
        expect(MediaCollection::from('brand_favicon'))->toBe(MediaCollection::FAVICON);
        expect(MediaCollection::cases())->toHaveCount(2);
        expect(MediaCollection::tryFrom('no-such-value'))->toBeNull();
    });

    test('52O1I-FR-BRAND-018: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(MediaCollection::LOGO->label())->toBe('Brand Logo');
        expect(MediaCollection::FAVICON->label())->toBe('Brand Favicon');
    });

    test('52O1I-FR-BRAND-018: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(MediaCollection::LOGO->label())->toBe('Logo Brand');
        expect(MediaCollection::FAVICON->label())->toBe('Favicon Brand');
    });
});
