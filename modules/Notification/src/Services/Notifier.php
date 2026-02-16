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
     * Store hashes of recently sent notifications to prevent duplicates.
     *
     * @var list<string>
     */
    protected static array $sentHashes = [];

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

        // Prevent duplicates by checking payload hash
        $hash = md5(json_encode([$payload['message'], $payload['type'], $payload['title']]));
        if (in_array($hash, self::$sentHashes)) {
            return $this;
        }
        self::$sentHashes[] = $hash;

        // 1. Session Storage (Standard Redirects & Initial Load)
        session()->flash('notify', $payload);
        session()->now('notify', $payload);

        // 2. Livewire Event Bus (Real-time within the same request)
        if (app()->bound(\Livewire\EventBus::class)) {
            app(\Livewire\EventBus::class)->trigger('notify', $payload);
        }

        return $this;
    }
}
