<?php

declare(strict_types=1);

namespace App\Domain\Core\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Dynamic Mail Configuration Service.
 * Injects database-driven SMTP settings into Laravel's mail system at runtime.
 *
 * S1 - Secure: Uses encrypted passwords from settings.
 * S2 - Sustain: Transparently overrides .env values with UI-configured values.
 */
final class MailConfiguration
{
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
        if (app()->runningUnitTests()) {
            return;
        }

        try {
            foreach (self::CONFIG_MAP as $settingKey => $configKey) {
                $value = Settings::get($settingKey);

                if (! is_null($value) && $value !== '') {
                    Config::set($configKey, $value);
                }
            }
        } catch (QueryException $e) {
            Log::warning('Failed to apply dynamic mail configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Unexpected error applying dynamic mail configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
