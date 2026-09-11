<?php

declare(strict_types=1);

use App\Modules\Settings\Enums\MediaCollection;

describe('MediaCollection enum', function (): void {
    test('cases carry the specified backing values: cases carry the specified backing values', function (): void {
        expect(MediaCollection::LOGO->value)->toBe('brand_logo');
        expect(MediaCollection::from('brand_logo'))->toBe(MediaCollection::LOGO);
        expect(MediaCollection::FAVICON->value)->toBe('brand_favicon');
        expect(MediaCollection::from('brand_favicon'))->toBe(MediaCollection::FAVICON);
        expect(MediaCollection::cases())->toHaveCount(2);
        expect(MediaCollection::tryFrom('no-such-value'))->toBeNull();
    });
    // NOTE: label() is not asserted here — it resolves to __('settings.media_collection.*')
    // while the lang file is lang/{en,id}/setting.php (singular), so the translator
    // returns the raw key. Recorded as drift for review.
});
