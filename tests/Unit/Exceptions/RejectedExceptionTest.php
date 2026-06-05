<?php

declare(strict_types=1);

use App\Exceptions\RejectedException;

test('rejected exception is throwable', function () {
    $e = new RejectedException('Application rejected');

    expect($e->getMessage())->toBe('Application rejected');
    expect($e->isUserFacing())->toBeTrue();
});
