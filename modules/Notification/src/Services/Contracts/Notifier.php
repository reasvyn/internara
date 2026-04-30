<?php

declare(strict_types=1);

namespace Modules\Notification\Services\Contracts;

/**
 * Interface Notifier
 *
 * Defines the standardized methods for dispatching system-wide notifications
 * and UI feedback across modules.
 */
interface Notifier
{
    /**
     * Notification types.
     */
    public const TYPE_SUCCESS = 'success';

    public const TYPE_ERROR = 'error';

    public const TYPE_WARNING = 'warning';

    public const TYPE_INFO = 'info';

    /**
     * Dispatch a success notification.
     */
    public function success(string $message, ?string $title = null, array $options = []): self;

    /**
     * Dispatch a error notification.
     */
    public function error(string $message, ?string $title = null, array $options = []): self;

    /**
     * Dispatch a warning notification.
     */
    public function warning(string $message, ?string $title = null, array $options = []): self;

    /**
     * Dispatch an informational notification.
     */
    public function info(string $message, ?string $title = null, array $options = []): self;

    /**
     * Generic method to dispatch a notification with a specific type.
     *
     * Options may include:
     * - timeout: integer (ms)
     * - autohide: boolean
     * - title: string
     */
    public function notify(
        string $message,
        string $type = self::TYPE_INFO,
        array $options = [],
    ): self;
}
