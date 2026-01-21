<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Modules\Notification\Contracts\Notifier;

/**
 * Class NotifierService
 *
 * Implements the Notifier contract to handle UI notifications via Livewire event dispatching.
 */
class NotifierService implements Notifier
{
    /**
     * {@inheritdoc}
     */
    public function success(string $message, array $options = []): void
    {
        $this->notify($message, 'success', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message, array $options = []): void
    {
        $this->notify($message, 'error', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message, array $options = []): void
    {
        $this->notify($message, 'warning', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function info(string $message, array $options = []): void
    {
        $this->notify($message, 'info', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function notify(string $message, string $type = 'info', array $options = []): void
    {
        // If we are within a Livewire request, we can dispatch the event
        if (app()->bound('livewire')) {
            /** @var \Livewire\Features\SupportEvents\EventBus $eventBus */
            $eventBus = app(\Livewire\Features\SupportEvents\EventBus::class);

            $eventBus->dispatch('notify', [
                'message' => $message,
                'type' => $type,
                'options' => $options,
            ]);
        }

        // In the future, this can also log to database or session for non-livewire requests
    }
}
