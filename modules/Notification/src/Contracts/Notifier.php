<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts;

/**
 * Notifier Contract
 *
 * Defines the standardized methods for dispatching system-wide notifications
 * and UI feedback across modules.
 */
interface Notifier
{
    /**
     * Dispatch a success notification.
     */
    public function success(string $message, array $options = []): void;

    /**
     * Dispatch an error notification.
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
    public function notify(string $message, string $type = 'info', array $options = []): void;
}
