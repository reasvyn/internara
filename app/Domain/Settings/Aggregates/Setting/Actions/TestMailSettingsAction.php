<?php

declare(strict_types=1);

namespace App\Domain\Settings\Aggregates\Setting\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Notifications\TestMailNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

class TestMailSettingsAction extends BaseAction
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

            return true;
        } catch (\Throwable $e) {
            SmartLogger::error('SMTP Test Failed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();

            return false;
        }
    }
}
