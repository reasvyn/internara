<?php

declare(strict_types=1);

namespace Modules\Notification\Services\Contracts;

use Illuminate\Notifications\Notification;

/**
 * Interface NotificationService
 *
 * Orchestrates the delivery of persistent system notifications and external user notifications.
 */
interface NotificationService
{
    /**
     * Send a notification to one or more recipients.
     *
     * @param mixed $recipients The notifiable entity or collection of entities.
     */
    public function send(mixed $recipients, Notification $notification): void;

    /**
     * Send a notification to one or more recipients immediately (without queuing).
     *
     * @param mixed $recipients The notifiable entity or collection of entities.
     */
    public function sendNow(mixed $recipients, Notification $notification): void;
}
