<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit\Services;

use Illuminate\Support\Facades\Session;
use Modules\Notification\Services\Contracts\Notifier as Contract;
use Modules\Notification\Services\Notifier;

test('it flashes notification to session', function () {
    $service = new Notifier;

    $service->success('Success Message');

    expect(Session::get('notify'))->toBe([
        'message' => 'Success Message',
        'type' => Contract::TYPE_SUCCESS,
        'options' => [],
    ]);
});

test('it handles info notification by default', function () {
    $service = new Notifier;

    $service->notify('Default Message');

    expect(Session::get('notify')['type'])->toBe(Contract::TYPE_INFO);
});
