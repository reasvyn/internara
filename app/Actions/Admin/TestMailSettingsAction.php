<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Notifications\User\TestMailNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class TestMailSettingsAction
{
    /**
     * Send a test email using the provided SMTP configuration.
     *
     * @param array<string, mixed> $config
     */
    public function execute(string $recipientEmail, array $config): bool
    {
        try {
            Config::set('mail.mailers.smtp.host', $config['host'] ?? '');
            Config::set('mail.mailers.smtp.port', (int) ($config['port'] ?? 587));
            Config::set('mail.mailers.smtp.encryption', $config['encryption'] ?? 'tls');
            Config::set('mail.mailers.smtp.username', $config['username'] ?? '');
            Config::set('mail.mailers.smtp.password', $config['password'] ?? '');
            Config::set('mail.from.address', $config['from_address'] ?? '');
            Config::set('mail.from.name', $config['from_name'] ?? '');

            Notification::route('mail', $recipientEmail)->notify(new TestMailNotification);

            return true;
        } catch (\Exception $e) {
            Log::error('SMTP Test Failed: '.$e->getMessage());

            return false;
        }
    }
}
