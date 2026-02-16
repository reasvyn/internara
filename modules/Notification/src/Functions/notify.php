<?php

declare(strict_types=1);

use Modules\Notification\Services\Contracts\Notifier;

if (!function_exists('notify')) {
    /**
     * Dispatch a system-wide notification or UI feedback.
     *
     * @param string $message The notification message.
     * @param string $type The type of notification (success, error, warning, info).
     * @param array $options Additional options for the notification.
     *
     * @return Notifier|void
     */
    function notify(
        ?string $message = null,
        string $type = Notifier::TYPE_INFO,
        array $options = [],
    ) {
        /** @var Notifier $notifier */
        $notifier = app(Notifier::class);

        if ($message === null) {
            return $notifier;
        }

        $notifier->notify($message, $type, $options);
    }
}
