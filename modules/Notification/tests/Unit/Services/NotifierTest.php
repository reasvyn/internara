<?php

declare(strict_types=1);

namespace Modules\Notification\Tests\Unit\Services;

use Modules\Notification\Contracts\Notifier as Contract;
use Modules\Notification\Services\Notifier;

test('it flashes notifications to session', function () {
    $notifier = new Notifier;
    $notifier->success('Success message');

    expect(session('notify'))->toBe([
        'message' => 'Success message',
        'type' => Contract::TYPE_SUCCESS,
        'options' => [],
    ]);
});

test('it uses info as default type', function () {
    $notifier = new Notifier;
    $notifier->notify('Info message');

    expect(session('notify'))->toBe([
        'message' => 'Info message',
        'type' => Contract::TYPE_INFO,
        'options' => [],
    ]);
});

test('it flashes to session when not in livewire context', function () {
    // Ensure Livewire manager is not indicating a livewire request
    $livewire = mock(\Livewire\LivewireManager::class);
    $livewire->shouldReceive('isLivewireRequest')->andReturn(false);
    app()->instance(\Livewire\LivewireManager::class, $livewire);

    $notifier = new Notifier;
    $notifier->error('System failure');

    expect(session('notify'))->toBe([
        'message' => 'System failure',
        'type' => Contract::TYPE_ERROR,
        'options' => [],
    ]);
});
