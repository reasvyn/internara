<?php

declare(strict_types=1);

namespace Modules\UI\Tests\Unit\Support;

use Modules\UI\Facades\SlotRegistry;
use Modules\UI\Support\Slot;

test('Slot::exists returns true if slot has components', function () {
    SlotRegistry::shouldReceive('hasSlot')->with('test-slot')->andReturn(true);

    expect(Slot::exists('test-slot'))->toBeTrue()->and(slot_exists('test-slot'))->toBeTrue();
});

test('Slot::exists returns false if slot is empty', function () {
    SlotRegistry::shouldReceive('hasSlot')->with('empty-slot')->andReturn(false);

    expect(Slot::exists('empty-slot'))->toBeFalse()->and(slot_exists('empty-slot'))->toBeFalse();
});
