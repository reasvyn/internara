<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Modules\Notification\Services\Contracts\Notifier as Contract;

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
    public function success(string $message, ?string $title = null, array $options = []): self
    {
        return $this->notify(
            $message,
            self::TYPE_SUCCESS,
            array_merge($options, ['title' => $title]),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message, ?string $title = null, array $options = []): self
    {
        return $this->notify(
            $message,
            self::TYPE_ERROR,
            array_merge($options, ['title' => $title]),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message, ?string $title = null, array $options = []): self
    {
        return $this->notify(
            $message,
            self::TYPE_WARNING,
            array_merge($options, ['title' => $title]),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function info(string $message, ?string $title = null, array $options = []): self
    {
        return $this->notify($message, self::TYPE_INFO, array_merge($options, ['title' => $title]));
    }

    /**
     * {@inheritdoc}
     */
    public function notify(
        string $message,
        string $type = self::TYPE_INFO,
        array $options = [],
    ): self {
        $payload = array_merge(
            [
                'message' => $message,
                'type' => $type,
                'title' => $options['title'] ?? null,
                'autohide' => $options['autohide'] ?? true,
                'timeout' => $options['timeout'] ?? 5000,
            ],
            $options,
        );

        // 1. Session Storage (Standard Redirects & Initial Load)
        // We append to an array to allow multiple notifications in a single request/session
        $notifications = session()->get('notify', []);
        if (! is_array($notifications)) {
            $notifications = [$notifications];
        }
        $notifications[] = $payload;

        session()->flash('notify', $notifications);
        session()->now('notify', $notifications);

        // 2. Livewire Event Bus (Real-time within the same request)
        if (app()->bound(\Livewire\EventBus::class)) {
            app(\Livewire\EventBus::class)->trigger('notify', $payload);
        }

        return $this;
    }
}
