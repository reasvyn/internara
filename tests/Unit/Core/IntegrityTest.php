<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Support\Integrity;

test('verify does not throw in testing environment', function () {
    expect(Integrity::verify())->toBeNull();
});

test('verify is callable', function () {
    Integrity::verify();

    expect(true)->toBeTrue();
});

test('integrity class is final', function () {
    $reflection = new \ReflectionClass(Integrity::class);

    expect($reflection->isFinal())->toBeTrue();
});
