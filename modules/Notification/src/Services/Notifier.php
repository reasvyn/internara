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
        $payload = [
            'message' => $message,
            'type' => $type,
            'options' => $options,
        ];

        // 1. Session Flash (Standard Redirects & Initial Load)
        session()->flash('notify', $payload);

        // 2. Livewire Event Bus (Real-time within the same request)
        if (app()->bound(\Livewire\EventBus::class)) {
            app(\Livewire\EventBus::class)->trigger('notify', $payload);
        }
    }
}
