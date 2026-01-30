<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit\Services;

use Modules\Notification\Contracts\Notifier as Contract;
use Modules\Notification\Services\Notifier;

test('it can dispatch notifications via Livewire event bus', function () {
    $eventBus = mock(\Livewire\Features\SupportEvents\EventBus::class);
    app()->bind('livewire', fn () => true);
    app()->instance(\Livewire\Features\SupportEvents\EventBus::class, $eventBus);

    $eventBus
        ->shouldReceive('dispatch')
        ->once()
        ->with('notify', [
            'message' => 'Success message',
            'type' => Contract::TYPE_SUCCESS,
            'options' => [],
        ]);

    $notifier = new Notifier;
    $notifier->success('Success message');
});

test('it uses info as default type', function () {
    $eventBus = mock(\Livewire\Features\SupportEvents\EventBus::class);
    app()->bind('livewire', fn () => true);
    app()->instance(\Livewire\Features\SupportEvents\EventBus::class, $eventBus);

    $eventBus
        ->shouldReceive('dispatch')
        ->once()
        ->with('notify', [
            'message' => 'Info message',
            'type' => Contract::TYPE_INFO,
            'options' => [],
        ]);

    $notifier = new Notifier;
    $notifier->notify('Info message');
});
