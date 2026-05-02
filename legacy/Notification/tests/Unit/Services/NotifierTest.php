<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit\Services;

use Flasher\Prime\FlasherInterface;
use Modules\Notification\Services\Notifier;

test('it delegates notification to flasher', function () {
    $flasher = mock(FlasherInterface::class);
    app()->instance('flasher', $flasher);

    $flasher->shouldReceive('addSuccess')->once()->with('Success Message', [], null);

    $service = new Notifier;
    $service->success('Success Message');
});

test('it handles info notification by default', function () {
    $flasher = mock(FlasherInterface::class);
    app()->instance('flasher', $flasher);

    $flasher->shouldReceive('addInfo')->once()->with('Default Message', [], null);

    $service = new Notifier;
    $service->notify('Default Message');
});
