<?php

declare(strict_types=1);

use App\Core\Services\AppIntegrity;
use RuntimeException;

it('FR-SUP9: verify() passes in the testing environment', function () {
    expect(fn () => AppIntegrity::verify())->not->toThrow(RuntimeException::class);
});
