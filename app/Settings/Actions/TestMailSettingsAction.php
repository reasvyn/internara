<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Core\Actions\BaseCommandAction;
use App\User\Notifications\TestMailNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

class TestMailSettingsAction extends BaseCommandAction
{
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

            $this->log('smtp_test_sent', null, ['recipient' => $recipientEmail]);

            return true;
        } catch (\Throwable $e) {
            $this->log('smtp_test_failed', null, ['error' => $e->getMessage()]);

            return false;
        }
    }
}
