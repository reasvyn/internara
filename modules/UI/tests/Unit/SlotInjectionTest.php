<?php

declare(strict_types=1);

namespace Modules\UI\Tests\Unit;

use Modules\UI\Facades\SlotManager;
use Modules\UI\Facades\SlotRegistry;

test('it can register and render a slot', function () {
    SlotRegistry::register('test.slot', function () {
        return 'Hello World';
    });

    $output = SlotManager::render('test.slot');

    expect($output)->toBe('Hello World');
});

test('it can render multiple components in a slot', function () {
    SlotRegistry::register('test.multi', function () {
        return 'A';
    });
    SlotRegistry::register('test.multi', function () {
        return 'B';
    });

    $output = SlotManager::render('test.multi');

    expect($output)->toBe('AB');
});
