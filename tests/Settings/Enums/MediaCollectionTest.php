<?php

declare(strict_types=1);

use App\Settings\Enums\MediaCollection;

test('media collection has logo and favicon cases', function () {
    expect(MediaCollection::cases())->toHaveCount(2);
    expect(MediaCollection::LOGO->value)->toBe('brand_logo');
    expect(MediaCollection::FAVICON->value)->toBe('brand_favicon');
});
