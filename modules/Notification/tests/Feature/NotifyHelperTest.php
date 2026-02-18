<?php

declare(strict_types=1);

use Flasher\Prime\FlasherInterface;
use Flasher\Prime\Notification\Envelope;
use Livewire\Component;
use Modules\Notification\Services\Contracts\Notifier;

test('notify helper sends notification via flasher', function () {
    $flasher = mock(FlasherInterface::class);
    app()->instance('flasher', $flasher);

    $flasher->shouldReceive('addSuccess')
        ->once()
        ->with('Success Operation', [], null);

    notify('Success Operation', 'success');
});

test('notify helper returns notifier instance when no parameters provided', function () {
    $result = notify();

    expect($result)->toBeInstanceOf(Notifier::class);
});
