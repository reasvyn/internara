<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Core\Actions\BaseCommandAction;
use App\User\Notifications\TestMailNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

final class TestMailSettingsAction extends BaseCommandAction
{
    public function execute(string $recipientEmail, array $config): bool
    {
        try {
            $this->sendWithTemporaryConfig($recipientEmail, $config);

            $this->log('smtp_test_sent', null, ['recipient' => $recipientEmail]);

            return true;
        } catch (\Throwable $e) {
            $this->log('smtp_test_failed', null, ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function sendWithTemporaryConfig(string $recipientEmail, array $config): void
    {
        $original = [
            'host' => Config::get('mail.mailers.smtp.host'),
            'port' => Config::get('mail.mailers.smtp.port'),
            'encryption' => Config::get('mail.mailers.smtp.encryption'),
            'username' => Config::get('mail.mailers.smtp.username'),
            'password' => Config::get('mail.mailers.smtp.password'),
            'from_address' => Config::get('mail.from.address'),
            'from_name' => Config::get('mail.from.name'),
        ];

        try {
            Config::set('mail.mailers.smtp.host', $config['host'] ?? '');
            Config::set('mail.mailers.smtp.port', (int) ($config['port'] ?? 587));
            Config::set('mail.mailers.smtp.encryption', $config['encryption'] ?? 'tls');
            Config::set('mail.mailers.smtp.username', $config['username'] ?? '');
            Config::set('mail.mailers.smtp.password', $config['password'] ?? '');
            Config::set('mail.from.address', $config['from_address'] ?? '');
            Config::set('mail.from.name', $config['from_name'] ?? '');

            Notification::route('mail', $recipientEmail)->notify(new TestMailNotification);
        } finally {
            Config::set('mail.mailers.smtp.host', $original['host']);
            Config::set('mail.mailers.smtp.port', $original['port']);
            Config::set('mail.mailers.smtp.encryption', $original['encryption']);
            Config::set('mail.mailers.smtp.username', $original['username']);
            Config::set('mail.mailers.smtp.password', $original['password']);
            Config::set('mail.from.address', $original['from_address']);
            Config::set('mail.from.name', $original['from_name']);
        }
    }
}
