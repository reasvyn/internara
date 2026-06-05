<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Exceptions;

use App\Core\Exceptions\ActionException;

class MockActionException extends ActionException {}

test('action exception is user facing', function () {
    $e = new MockActionException('Action failed');

    expect($e->isUserFacing())->toBeTrue();
    expect($e->shouldReport())->toBeTrue();
});
