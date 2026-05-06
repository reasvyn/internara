<?php

declare(strict_types=1);

namespace App\Domain\Setup\Services;

use App\Domain\Setup\Data\AuditCheck;
use App\Domain\Setup\Data\AuditReport;
use App\Domain\Setup\Enums\AuditCategory;
use App\Domain\Shared\Enums\AuditStatus;

/**
 * Pure domain service that audits system requirements.
 * Zero Laravel dependencies — uses only native PHP functions.
 */
final class EnvironmentAuditor
{
    public const string REQUIRED_PHP_VERSION = '8.4.0';

    public const array REQUIRED_EXTENSIONS = ['bcmath', 'ctype', 'fileinfo', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'curl', 'gd', 'intl', 'zip'];

    public const array RECOMMENDED_EXTENSIONS = ['redis', 'pcntl', 'posix'];

    public function audit(): AuditReport
    {
        return new AuditReport([
            ...$this->checkPhpVersion(),
            ...$this->checkExtensions(self::REQUIRED_EXTENSIONS, critical: true),
            ...$this->checkExtensions(self::RECOMMENDED_EXTENSIONS, critical: false),
            ...$this->checkPermissions(),
            ...$this->checkDatabaseConnection(),
            ...$this->checkTerminalSupport(),
        ]);
    }

    /** @return list<AuditCheck> */
    private function checkPhpVersion(): array
    {
        return [new AuditCheck(
            category: AuditCategory::Requirements,
            nameKey: 'php_version',
            status: version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '>=') ? AuditStatus::Pass : AuditStatus::Fail,
            messageKey: version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '>=') ? 'php_version_pass' : 'php_version_fail',
            nameParams: ['required' => self::REQUIRED_PHP_VERSION],
            messageParams: ['current' => PHP_VERSION, 'required' => self::REQUIRED_PHP_VERSION],
        )];
    }

    /** @return list<AuditCheck> */
    private function checkExtensions(array $extensions, bool $critical): array
    {
        $checks = [];

        foreach ($extensions as $extension) {
            $loaded = extension_loaded($extension);
            $key = $critical ? 'extension' : 'recommended_extension';
            $passKey = $critical ? 'extension_pass' : 'recommended_pass';
            $failKey = $critical ? 'extension_fail' : 'recommended_fail';

            $checks[] = new AuditCheck(
                category: $critical ? AuditCategory::Requirements : AuditCategory::Recommendations,
                nameKey: $key,
                status: $loaded ? AuditStatus::Pass : ($critical ? AuditStatus::Fail : AuditStatus::Warn),
                messageKey: $loaded ? $passKey : $failKey,
                nameParams: ['extension' => $extension],
                messageParams: ['extension' => $extension],
            );
        }

        return $checks;
    }

    /** @return list<AuditCheck> */
    private function checkPermissions(): array
    {
        $checks = [];

        foreach (['storage', 'bootstrap/cache'] as $path) {
            $writable = is_writable(base_path($path));
            $checks[] = new AuditCheck(
                category: AuditCategory::Permissions,
                nameKey: 'writable_dir',
                status: $writable ? AuditStatus::Pass : AuditStatus::Fail,
                messageKey: $writable ? 'writable_pass' : 'writable_fail',
                nameParams: ['directory' => $path],
                messageParams: ['directory' => $path],
            );
        }

        return $checks;
    }

    /** @return list<AuditCheck> */
    private function checkDatabaseConnection(): array
    {
        $connected = $this->testDatabaseConnection();

        return [new AuditCheck(
            category: AuditCategory::Database,
            nameKey: 'db_connection',
            status: $connected ? AuditStatus::Pass : AuditStatus::Fail,
            messageKey: $connected ? 'db_pass' : 'db_fail',
            messageParams: ['driver' => getenv('DB_CONNECTION') ?: 'mysql', 'version' => ''],
        )];
    }

    /** @return list<AuditCheck> */
    private function checkTerminalSupport(): array
    {
        return [
            new AuditCheck(
                category: AuditCategory::Terminal,
                nameKey: 'terminal_animations',
                status: function_exists('pcntl_fork') ? AuditStatus::Pass : AuditStatus::Warn,
                messageKey: function_exists('pcntl_fork') ? 'terminal_animations_pass' : 'terminal_animations_fail',
            ),
            new AuditCheck(
                category: AuditCategory::Terminal,
                nameKey: 'terminal_interactive',
                status: function_exists('posix_isatty') ? AuditStatus::Pass : AuditStatus::Warn,
                messageKey: function_exists('posix_isatty') ? 'terminal_interactive_pass' : 'terminal_interactive_fail',
            ),
        ];
    }

    private function testDatabaseConnection(): bool
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_DATABASE') ?: 'forge';
        $username = getenv('DB_USERNAME') ?: 'forge';
        $password = getenv('DB_PASSWORD') ?: '';

        try {
            if ($driver === 'sqlite') {
                $path = database_path($database);

                return file_exists($path) || is_writable(dirname($path));
            }

            $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";
            new \PDO($dsn, $username, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
