<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Modules\Notification\Contracts\Notifier as Contract;

/**
 * Class Notifier
 *
 * Implements the Notifier contract to handle UI notifications via Livewire event dispatching.
 */
class Notifier implements Contract
{
    /**
     * {@inheritdoc}
     */
    public function success(string $message, array $options = []): void
    {
        $this->notify($message, self::TYPE_SUCCESS, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message, array $options = []): void
    {
        $this->notify($message, self::TYPE_ERROR, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message, array $options = []): void
    {
        $this->notify($message, self::TYPE_WARNING, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function info(string $message, array $options = []): void
    {
        $this->notify($message, self::TYPE_INFO, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function notify(
        string $message,
        string $type = self::TYPE_INFO,
        array $options = [],
    ): void {
        // 1. Handle Livewire Dispatch (Real-time)
        if (app()->bound(\Livewire\LivewireManager::class) && app(\Livewire\LivewireManager::class)->isLivewireRequest()) {
            /** @var \Livewire\Features\SupportEvents\EventBus $eventBus */
            $eventBus = app(\Livewire\Features\SupportEvents\EventBus::class);

            $eventBus->dispatch('notify', [
                'message' => $message,
                'type' => $type,
                'options' => $options,
            ]);

            return;
        }

        // 2. Handle Session Flash (Standard Redirects)
        session()->flash('notify', [
            'message' => $message,
            'type' => $type,
            'options' => $options,
        ]);
    }
}
