<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Asset;

test('Asset::sharedUrl returns correct asset path', function () {
    $url = Asset::sharedUrl('js/app.js');

    expect($url)->toContain('modules/shared/js/app.js')->and(shared_url('js/app.js'))->toBe($url);
});

test('Asset::sharedUrl handles leading slashes', function () {
    $url = Asset::sharedUrl('/css/style.css');

    expect($url)
        ->toContain('modules/shared/css/style.css')
        ->and($url)
        ->not->toContain('shared//css');
});
