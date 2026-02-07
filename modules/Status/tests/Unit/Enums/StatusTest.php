<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Unit\Enums;

use Modules\Status\Enums\Status;

test('it returns correct color for status', function () {
    expect(Status::ACTIVE->color())
        ->toBe('success')
        ->and(Status::PENDING->color())
        ->toBe('warning')
        ->and(Status::INACTIVE->color())
        ->toBe('error');
});

test('it returns correct translation key', function () {
    expect(Status::ACTIVE->label())->toBe('status::status.active');
});
