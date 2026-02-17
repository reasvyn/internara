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
     * Component mount logic.
     */
    public function mount(): void
    {
        $this->handleSessionNotifications();
    }

    /**
     * Component boot logic.
     */
    public function boot(EventBus $bus): void
    {
        $bus->on('notify', function (array $payload) {
            $this->dispatch('notify', $payload);
        });

        $this->handleSessionNotifications();
    }

    /**
     * Handle notifications stored in the session.
     */
    protected function handleSessionNotifications(): void
    {
        if ($payload = session('notify')) {
            if (is_array($payload) && ! isset($payload['message'])) {
                foreach ($payload as $item) {
                    $this->dispatch('notify', $item);
                }
            } else {
                $this->dispatch('notify', $payload);
            }

            session()->forget('notify');
        }
    }

    /**
     * Render the component (invisible).
     */
    public function render(): string
    {
        return '<div></div>';
    }
}
