<?php

declare(strict_types=1);

use App\Support\Integrity;

test('verify runs without exception in testing environment', function () {
    expect(Integrity::verify())->toBeNull();
});
