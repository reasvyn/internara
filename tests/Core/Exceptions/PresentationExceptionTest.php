<?php

declare(strict_types=1);

namespace Tests\Core\Exceptions;

use App\Core\Exceptions\PresentationException;

class MockPresentationException extends PresentationException {}

test('presentation exception is user facing', function () {
    $e = new MockPresentationException('Render error');

    expect($e->isUserFacing())->toBeTrue();
    expect($e->shouldReport())->toBeTrue();
});
