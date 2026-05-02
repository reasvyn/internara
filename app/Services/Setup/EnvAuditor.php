<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Services\Setup\SetupRequirementRegistry;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

/**
 * Performs pre-flight system checks before allowing installation.
 *
 * S1 - Secure: Sanitizes errors to prevent credential exposure.
 * S2 - Sustain: Clear, actionable check results for operators.
 * S3 - Scalable: Uses Registry for dynamic requirement management.
 */
class EnvAuditor
{
    /**
     * Minimum PHP version required.
     */
    private const MIN_PHP_VERSION = '8.4.0';

    public function __construct(private SetupRequirementRegistry $registry)
    {
    }

    /**
     * Run all pre-flight checks and return results.
     */
    public function audit(): array
    {
        $categories = [
            'requirements' => [
                'label' => __('setup.wizard.system_requirements'),
                'checks' => [$this->checkPhpVersion(), ...$this->checkRequiredExtensions()],
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
                'checks' => [...$this->checkRecommendedExtensions(), $this->checkAppKey()],
            ],
        ];

        $allChecks = [];
        foreach ($categories as $cat) {
            $allChecks = [...$allChecks, ...$cat['checks']];
        }

        return [
            'passed' => collect($allChecks)->every(fn($c) => $c['status'] !== 'fail'),
            'categories' => $categories,
        ];
    }

    private function checkPhpVersion(): array
    {
        $current = PHP_VERSION;
        $meets = version_compare($current, self::MIN_PHP_VERSION, '>=');

        return [
            'name' => __('setup.checks.php_version', ['required' => self::MIN_PHP_VERSION]),
            'status' => $meets ? 'pass' : 'fail',
            'message' => $meets
                ? __('setup.checks.php_version_pass', ['current' => $current])
                : __('setup.checks.php_version_fail', [
                    'current' => $current,
                    'required' => self::MIN_PHP_VERSION,
                ]),
        ];
    }

    private function checkRequiredExtensions(): array
    {
        $checks = [];
        foreach ($this->registry->getRequiredExtensions() as $ext) {
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

    private function checkRecommendedExtensions(): array
    {
        $checks = [];
        foreach ($this->registry->getRecommendedExtensions() as $ext) {
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

    private function checkWritableDirectories(): array
    {
        $checks = [];
        foreach ($this->registry->getWritableDirs() as $dir) {
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
                'message' => __('setup.checks.db_pass', [
                    'driver' => $driver,
                    'version' => $version,
                ]),
            ];
        } catch (PDOException $e) {
            return [
                'name' => __('setup.checks.db_connection'),
                'status' => 'fail',
                'message' => __('setup.checks.db_fail_message', [
                    'error' => $this->sanitizeError($e->getMessage()),
                ]),
            ];
        }
    }

    private function checkAppKey(): array
    {
        $key = config('app.key');
        return [
            'name' => __('setup.checks.app_key'),
            'status' => ($key !== null && $key !== '') ? 'pass' : 'fail',
            'message' => ($key !== null && $key !== '') 
                ? __('setup.checks.app_key_pass') 
                : __('setup.checks.app_key_fail'),
        ];
    }

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

        if (strlen($message) > 120) {
            $message = substr($message, 0, 117) . '...';
        }

        return $message;
    }
}
