<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Exceptions;

use App\Core\Exceptions\InfrastructureException;

class MockInfrastructureException extends InfrastructureException {}

test('infrastructure exception is not user facing', function () {
    $e = new MockInfrastructureException('Connection error');

    expect($e->isUserFacing())->toBeFalse();
    expect($e->shouldReport())->toBeTrue();
});
