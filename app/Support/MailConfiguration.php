<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * Dynamic Mail Configuration Service.
 * Injects database-driven SMTP settings into Laravel's mail system at runtime.
 *
 * S1 - Secure: Uses encrypted passwords from settings.
 * S2 - Sustain: Transparently overrides .env values with UI-configured values.
 */
final class MailConfiguration
{
    /**
     * Map setting keys to Laravel config keys.
     */
    private const CONFIG_MAP = [
        'mail_host' => 'mail.mailers.smtp.host',
        'mail_port' => 'mail.mailers.smtp.port',
        'mail_username' => 'mail.mailers.smtp.username',
        'mail_password' => 'mail.mailers.smtp.password',
        'mail_encryption' => 'mail.mailers.smtp.encryption',
        'mail_from_address' => 'mail.from.address',
        'mail_from_name' => 'mail.from.name',
    ];

    /**
     * Apply dynamic mail settings to the application configuration.
     */
    public static function apply(): void
    {
        // We only apply if mail_host is set in the database
        $host = Settings::get('mail_host');

        if (empty($host)) {
            return;
        }

        foreach (self::CONFIG_MAP as $settingKey => $configKey) {
            $value = Settings::get($settingKey);

            if (! is_null($value) && $value !== '') {
                Config::set($configKey, $value);
            }
        }
    }
}
