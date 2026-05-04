<?php

declare(strict_types=1);

namespace App\Domain\Core\Support;

use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Core System Integrity and Attribution Protection Service.
 *
 * S1 - Secure: Protects the core attribution and prevents unauthorized modification.
 */
final class Integrity
{
    private const AUTHOR_NAME = 'Reas Vyn';

    private const EXIT_CODE = 1;

    /**
     * Verify the system attribution and metadata integrity.
     */
    public static function verify(): void
    {
        $basePath = dirname(__DIR__, 4);
        $path = $basePath.'/composer.json';

        if (self::isTestingEnvironment()) {
            self::restoreBackupIfNeeded($basePath, $path);

            return;
        }

        self::verifyComposerFile($path);
    }

    /**
     * Check if running in a testing environment.
     */
    private static function isTestingEnvironment(): bool
    {
        return class_exists(TestCase::class, false)
            || defined('PHPUNIT_COMPOSER_INSTALL')
            || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing')
            || (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'testing');
    }

    /**
     * Restore metadata backup if composer.json is missing during testing.
     */
    private static function restoreBackupIfNeeded(string $basePath, string $path): void
    {
        if (file_exists($path)) {
            return;
        }

        $backupPath = $basePath.'/app_info.json.backup';

        if (! file_exists($backupPath)) {
            return;
        }

        try {
            copy($backupPath, $path);
        } catch (\Throwable $e) {
            Log::warning('Failed to restore metadata backup during testing', [
                'backup_path' => $backupPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify composer.json exists and author attribution is valid.
     */
    private static function verifyComposerFile(string $path): void
    {
        if (! file_exists($path)) {
            self::fatal('Core system metadata (composer.json) is missing.');
        }

        try {
            $content = file_get_contents($path);

            if ($content === false) {
                self::fatal('Failed to read core system metadata file.');
            }

            $info = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                self::fatal('Core system metadata file contains invalid JSON: '.json_last_error_msg());
            }

            $info = is_array($info) ? $info : [];
            $authorName = $info['authors'][0]['name'] ?? '';

            if (! hash_equals(self::AUTHOR_NAME, $authorName)) {
                self::fatal(
                    'Attribution Error: Unauthorized author modification detected. '.
                    'This system requires attribution to the original author.'
                );
            }
        } catch (RuntimeException $e) {
            self::fatal('System integrity verification failed: '.$e->getMessage());
        }
    }

    /**
     * Trigger a fatal system error with logging.
     */
    private static function fatal(string $message): never
    {
        Log::critical('System integrity check failed', ['message' => $message]);

        $appName = 'Application';

        $basePath = dirname(__DIR__, 4);
        $path = $basePath.'/composer.json';

        if (file_exists($path)) {
            try {
                $content = file_get_contents($path);

                if ($content !== false) {
                    $info = json_decode($content, true);
                    $appName = is_array($info) ? ($info['name'] ?? 'Application') : 'Application';
                }
            } catch (\Throwable) {
                // Silently use default app name
            }
        }

        if (PHP_SAPI === 'cli') {
            echo "\n\033[41m FATAL ERROR \033[0m\n";
            echo "\033[31m {$message} \033[0m\n\n";
            exit(1);
        }

        if (! headers_sent()) {
            header('HTTP/1.1 403 Forbidden');
        }

        echo "<html><body style='font-family:sans-serif; background:#fef2f2; color:#991b1b; padding:4rem; text-align:center;'>";
        echo "<h1 style='font-size:3rem; margin-bottom:1rem;'>System Integrity Breach</h1>";
        echo "<p style='font-size:1.25rem; max-width:600px; margin:0 auto;'>{$message}</p>";
        echo "<hr style='border:0; border-top:1px solid #fee2e2; margin:2rem auto; max-width:100px;'>";
        echo "<footer style='opacity:0.5; font-size:0.875rem;'>{$appName} Core Protection System</footer>";
        echo '</body></html>';
        exit(1);
    }
}
