<?php

declare(strict_types=1);

use Livewire\Component;
use Modules\Notification\Services\Contracts\Notifier;

class TestNotifyComponent extends Component
{
    public function triggerSuccess()
    {
        notify('Success Operation', 'success');
    }

    public function render()
    {
        return '<div></div>';
    }
}

test('notify helper flashes session', function () {
    notify('Success Operation', 'success');

    expect(session('notify'))->toBe([
        'message' => 'Success Operation',
        'type' => 'success',
        'options' => [],
    ]);
});

test('notify helper returns notifier instance when no parameters provided', function () {
    $result = notify();

    expect($result)->toBeInstanceOf(Notifier::class);
});
