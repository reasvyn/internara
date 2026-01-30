<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts;

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
    public function success(string $message, array $options = []): void;

    /**
     * Dispatch a error notification.
     */
    public function error(string $message, array $options = []): void;

    /**
     * Dispatch a warning notification.
     */
    public function warning(string $message, array $options = []): void;

    /**
     * Dispatch an informational notification.
     */
    public function info(string $message, array $options = []): void;

    /**
     * Generic method to dispatch a notification with a specific type.
     */
    public function notify(
        string $message,
        string $type = self::TYPE_INFO,
        array $options = [],
    ): void;
}
