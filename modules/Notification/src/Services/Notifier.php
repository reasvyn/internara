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
        $title = $options['title'] ?? null;
        $flasher = \flash();

        $envelop = match ($type) {
            self::TYPE_SUCCESS => $flasher->addSuccess($message, [], $title),
            self::TYPE_ERROR => $flasher->addError($message, [], $title),
            self::TYPE_WARNING => $flasher->addWarning($message, [], $title),
            default => $flasher->addInfo($message, [], $title),
        };

        return $this;
    }
}
