<?php

declare(strict_types=1);

use App\Core\Support\Spotlight;

test('spotlight can be instantiated', function () {
    $spotlight = app(Spotlight::class);

    expect($spotlight)->toBeInstanceOf(Spotlight::class);
});
