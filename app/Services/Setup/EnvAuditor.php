<?php

declare(strict_types=1);

namespace App\Services\Setup;

use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

/**
 * Performs pre-flight system checks before allowing installation.
 *
 * S1 - Secure: Sanitizes errors to prevent credential exposure.
 * S2 - Sustain: Clear, actionable check results for operators.
 */
class EnvAuditor
{
    /**
     * Minimum PHP version required.
     */
    private const MIN_PHP_VERSION = '8.4.0';

    /**
     * Required PHP extensions for Laravel 13 + Core Features.
     *
     * @var array<int, string>
     */
    private const REQUIRED_EXTENSIONS = [
        'bcmath',
        'ctype',
        'fileinfo',
        'mbstring',
        'openssl',
        'pdo',
        'tokenizer',
        'xml',
        'curl',
        'gd',
        'intl',
        'zip',
    ];

    /**
     * Recommended PHP extensions for production.
     *
     * @var array<int, string>
     */
    private const RECOMMENDED_EXTENSIONS = [
        'redis',
        'pcntl',
        'posix',
    ];

    /**
     * Directories that must be writable.
     *
     * @var array<int, string>
     */
    private const WRITABLE_DIRS = [
        'storage',
        'bootstrap/cache',
        'database',
    ];

    /**
     * Run all pre-flight checks and return results.
     *
     * @return array{
     *     passed: bool,
     *     categories: array<string, array{
     *         label: string,
     *         checks: array<int, array{
     *             name: string,
     *             status: 'pass'|'fail'|'warn',
     *             message: string,
     *         }>
     *     }>,
     * }
     */
    public function audit(): array
    {
        $categories = [
            'requirements' => [
                'label' => __('setup.wizard.system_requirements'),
                'checks' => [
                    $this->checkPhpVersion(),
                    ...$this->checkRequiredExtensions(),
                ],
            ],
            'permissions' => [
                'label' => __('setup.wizard.permissions'),
                'checks' => $this->checkWritableDirectories(),
            ],
            'database' => [
                'label' => __('setup.wizard.database'),
                'checks' => [$this->checkDatabaseConnection()],
            ],
            'recommendations' => [
                'label' => __('setup.wizard.recommendations'),
                'checks' => [
                    ...$this->checkRecommendedExtensions(),
                    $this->checkAppKey(),
                ],
            ],
        ];

        $allChecks = [];
        foreach ($categories as $cat) {
            $allChecks = [...$allChecks, ...$cat['checks']];
        }

        return [
            'passed' => collect($allChecks)->every(fn ($c) => $c['status'] !== 'fail'),
            'categories' => $categories,
        ];
    }

    /**
     * Check PHP version meets minimum requirement.
     */
    private function checkPhpVersion(): array
    {
        $current = PHP_VERSION;
        $meets = version_compare($current, self::MIN_PHP_VERSION, '>=');

        return [
            'name' => __('setup.checks.php_version', ['required' => self::MIN_PHP_VERSION]),
            'status' => $meets ? 'pass' : 'fail',
            'message' => $meets
                ? __('setup.checks.php_version_pass', ['current' => $current])
                : __('setup.checks.php_version_fail', ['current' => $current, 'required' => self::MIN_PHP_VERSION]),
        ];
    }

    /**
     * Check all required PHP extensions are loaded.
     *
     * @return array<int, array>
     */
    private function checkRequiredExtensions(): array
    {
        $checks = [];

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);

            $checks[] = [
                'name' => __('setup.checks.extension', ['extension' => $ext]),
                'status' => $loaded ? 'pass' : 'fail',
                'message' => $loaded
                    ? __('setup.checks.extension_pass', ['extension' => $ext])
                    : __('setup.checks.extension_fail', ['extension' => $ext]),
            ];
        }

        return $checks;
    }

    /**
     * Check recommended PHP extensions.
     *
     * @return array<int, array>
     */
    private function checkRecommendedExtensions(): array
    {
        $checks = [];

        foreach (self::RECOMMENDED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);

            $checks[] = [
                'name' => __('setup.checks.recommended_extension', ['extension' => $ext]),
                'status' => $loaded ? 'pass' : 'warn',
                'message' => $loaded
                    ? __('setup.checks.recommended_pass', ['extension' => $ext])
                    : __('setup.checks.recommended_fail', ['extension' => $ext]),
            ];
        }

        return $checks;
    }

    /**
     * Check required directories are writable.
     *
     * @return array<int, array>
     */
    private function checkWritableDirectories(): array
    {
        $checks = [];

        foreach (self::WRITABLE_DIRS as $dir) {
            $path = base_path($dir);
            $writable = is_writable($path);

            $checks[] = [
                'name' => __('setup.checks.writable_dir', ['directory' => $dir]),
                'status' => $writable ? 'pass' : 'fail',
                'message' => $writable
                    ? __('setup.checks.writable_pass', ['directory' => $dir])
                    : __('setup.checks.writable_fail', ['directory' => $dir]),
            ];
        }

        return $checks;
    }

    /**
     * Check database connectivity.
     * S1: Sanitizes error messages to prevent credential exposure.
     */
    private function checkDatabaseConnection(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();

            if ($pdo === null) {
                return [
                    'name' => __('setup.checks.db_connection'),
                    'status' => 'fail',
                    'message' => __('setup.checks.db_fail'),
                ];
            }

            $driver = $connection->getDriverName();
            $version = $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION) ?? 'unknown';

            return [
                'name' => __('setup.checks.db_connection'),
                'status' => 'pass',
                'message' => __('setup.checks.db_pass', ['driver' => $driver, 'version' => $version]),
            ];
        } catch (PDOException $e) {
            // S1: Never expose connection details or credentials in error messages
            return [
                'name' => __('setup.checks.db_connection'),
                'status' => 'fail',
                'message' => __('setup.checks.db_fail_message', ['error' => $this->sanitizeError($e->getMessage())]),
            ];
        }
    }

    /**
     * Check if application key is set.
     */
    private function checkAppKey(): array
    {
        $key = config('app.key');

        if ($key === null || $key === '') {
            return [
                'name' => __('setup.checks.app_key'),
                'status' => 'fail',
                'message' => __('setup.checks.app_key_fail'),
            ];
        }

        return [
            'name' => __('setup.checks.app_key'),
            'status' => 'pass',
            'message' => __('setup.checks.app_key_pass'),
        ];
    }

    /**
     * Sanitize error messages to prevent credential exposure.
     * S1: Remove any connection strings, credentials, or paths from error output.
     */
    private function sanitizeError(string $message): string
    {
        $patterns = [
            '/(?:password|username|host|port|dbname|dsn)\s*=\s*\S+/i',
            '/(?:mysql|pgsql|sqlite|sqlsrv):\/\/[^\s]+/i',
            '/\/[^\s]+\/\.env/',
            '/Access denied for user\s+[^\s]+/',
        ];

        foreach ($patterns as $pattern) {
            $message = preg_replace($pattern, '[redacted]', $message) ?? $message;
        }

        // Truncate to prevent leaking sensitive context
        if (strlen($message) > 120) {
            $message = substr($message, 0, 117).'...';
        }

        return $message;
    }
}
