<?php

declare(strict_types=1);

use App\Support\Integrity;

it('verifies system integrity in normal environment', function () {
    // Should not throw any exceptions in normal environment
    expect(function () {
        Integrity::verify();
    })->not->toThrow(RuntimeException::class);
})->skip('Requires actual composer.json integrity check');

it('does not verify in testing environment', function () {
    // In testing, it should just pass without checking
    Integrity::verify();

    expect(true)->toBeTrue();
});
