<?php

declare(strict_types=1);

namespace Modules\UI\Livewire;

use Livewire\Component;
use Livewire\EventBus;

/**
 * NotificationBridge
 *
 * This component acts as a bridge between the server-side EventBus
 * and the client-side browser events. It allows Services to trigger
 * toasts during Livewire requests.
 */
class NotificationBridge extends Component
{
    /**
     * Component boot logic.
     */
    public function boot(EventBus $bus): void
    {
        $bus->on('notify', function (array $payload) {
            $this->dispatch('notify-browser', $payload);
        });
    }

    /**
     * Render the component (invisible).
     */
    public function render(): string
    {
        return '<div></div>';
    }
}
