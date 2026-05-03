declare(strict_types=1);

namespace App\Domain\Core\Support;

use PHPUnit\Framework\TestCase;

/**
 * Core System Integrity and Attribution Protection Service.
 *
 * S1 - Secure: Protects the core attribution and prevents unauthorized modification.
 */
final class Integrity
{
    /**
     * The verified author identity hash (MD5).
     */
    private const AUTHOR_HASH = '29625713fdf561ab06b5dce01ed2fd83';

    /**
     * Verify the system attribution and metadata integrity.
     */
    public static function verify(): void
    {
        // We use absolute path calculation instead of Laravel helpers
        // to support early-stage boot checking (before app is fully loaded).
        $basePath = dirname(__DIR__, 4);
        $path = $basePath.'/composer.json';

        // Skip during testing - detect PHPUnit context early
        if (
            class_exists(TestCase::class, false) ||
            defined('PHPUNIT_COMPOSER_INSTALL') ||
            (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') ||
            (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'testing')
        ) {
            // Ensure app_info.json exists with proper author for tests
            if (! file_exists($path)) {
                $backupPath = $basePath.'/app_info.json.backup';
                if (file_exists($backupPath)) {
                    copy($backupPath, $path);
                }
            }

            return;
        }

        if (! file_exists($path)) {
            self::fatal('Core system metadata (composer.json) is missing.');
        }

        $content = file_get_contents($path);
        $info = json_decode($content, true) ?? [];
        $authors = (array) ($info['authors'] ?? []);
        $authorName = $authors[0]['name'] ?? '';

        if (md5($authorName) !== self::AUTHOR_HASH) {
            self::fatal(
                'Attribution Error: Unauthorized author modification detected. '.
                    'This system requires attribution to the original author (Reas Vyn).',
            );
        }
    }

    /**
     * Trigger a fatal system error.
     */
    private static function fatal(string $message): never
    {
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
        echo "<footer style='opacity:0.5; font-size:0.875rem;'>Internara Core Protection System</footer>";
        echo '</body></html>';
        exit();
    }
}
