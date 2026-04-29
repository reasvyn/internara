<?php

declare(strict_types=1);

namespace Modules\Status\Tests\Unit\Enums;

use Modules\Status\Enums\Status;

test('it returns correct color for status', function () {
    expect(Status::VERIFIED->color())
        ->toBe('#10b981')
        ->and(Status::PENDING->color())
        ->toBe('#f59e0b')
        ->and(Status::SUSPENDED->color())
        ->toBe('#ef4444');
});

test('it returns correct translation key', function () {
    expect(Status::VERIFIED->label())->toBe('status::status.verified');
});

test('it returns correct description', function () {
    expect(Status::VERIFIED->description())
        ->toContain('terverifikasi');
});

test('it validates transitions correctly', function () {
    expect(Status::PENDING->canTransitionTo(Status::VERIFIED))->toBeTrue()
        ->and(Status::VERIFIED->canTransitionTo(Status::INACTIVE))->toBeTrue()
        ->and(Status::PROTECTED->canTransitionTo(Status::VERIFIED))->toBeFalse();
});
