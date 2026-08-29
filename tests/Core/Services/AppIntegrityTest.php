<?php

declare(strict_types=1);

use App\Modules\Core\Services\AppIntegrity;

test('C8F0D-FR-SUP9: verify() passes in the testing environment', function () {
    expect(fn () => AppIntegrity::verify())->not->toThrow(RuntimeException::class);
});
