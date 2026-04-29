<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Asset;

describe('Asset Utility', function () {
    test('it generates correct asset paths', function () {
        // Assuming public path logic
        expect(Asset::sharedUrl('css/app.css'))->toContain('/css/app.css');
    });
});
