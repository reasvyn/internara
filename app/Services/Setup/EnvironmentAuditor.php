<?php

declare(strict_types=1);

namespace App\Services\Setup;

use App\Data\Audit\AuditCheck;
use App\Data\Audit\AuditReport;
use App\Enums\Setup\AuditCategory;
use App\Enums\Shared\AuditStatus;

/**
 * Pure domain service that audits system requirements.
 * Requirements sourced from config/setup.php.
 */
class EnvironmentAuditor
{
    public function audit(): AuditReport
    {
        return new AuditReport([
            ...$this->checkPhpVersion(),
            ...$this->checkExtensions(config('setup.requirements.extensions'), critical: true),
            ...$this->checkExtensions(config('setup.requirements.recommended_extensions'), critical: false),
            ...$this->checkPermissions(),
            ...$this->checkDatabaseConnection(),
            ...$this->checkTerminalSupport(),
        ]);
    }

    /** @return AuditCheck[] */
    private function checkPhpVersion(): array
    {
        $required = config('setup.requirements.php_version');
        $pass = version_compare(PHP_VERSION, $required, '>=');

        return [new AuditCheck(
            category: AuditCategory::Requirements,
            nameKey: 'php_version',
            status: $pass ? AuditStatus::Pass : AuditStatus::Fail,
            messageKey: $pass ? 'php_version_pass' : 'php_version_fail',
            nameParams: ['required' => $required],
            messageParams: ['current' => PHP_VERSION, 'required' => $required],
        )];
    }

    /** @return AuditCheck[] */
    private function checkExtensions(array $extensions, bool $critical): array
    {
        $checks = [];

        foreach ($extensions as $extension) {
            $loaded = extension_loaded($extension);
            $failStatus = $critical ? AuditStatus::Fail : AuditStatus::Warn;

            $checks[] = new AuditCheck(
                category: $critical ? AuditCategory::Requirements : AuditCategory::Recommendations,
                nameKey: $critical ? 'extension' : 'recommended_extension',
                status: $loaded ? AuditStatus::Pass : $failStatus,
                messageKey: $loaded ? ($critical ? 'extension_pass' : 'recommended_pass') : ($critical ? 'extension_fail' : 'recommended_fail'),
                nameParams: ['extension' => $extension],
                messageParams: ['extension' => $extension],
            );
        }

        return $checks;
    }

    /** @return AuditCheck[] */
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

    /** @return AuditCheck[] */
    private function checkDatabaseConnection(): array
    {
        $driver = config('database.default', 'mysql');
        $connected = $this->testDatabaseConnection($driver);

        return [new AuditCheck(
            category: AuditCategory::Database,
            nameKey: 'db_connection',
            status: $connected ? AuditStatus::Pass : AuditStatus::Fail,
            messageKey: $connected ? 'db_pass' : 'db_fail',
            nameParams: ['driver' => $driver],
            messageParams: ['driver' => $driver],
        )];
    }

    /** @return AuditCheck[] */
    private function testDatabaseConnection(string $driver): bool
    {
        $host = config('database.connections.'.$driver.'.host') ?: '127.0.0.1';
        $port = config('database.connections.'.$driver.'.port') ?: '3306';
        $database = config('database.connections.'.$driver.'.database') ?: 'forge';
        $username = config('database.connections.'.$driver.'.username') ?: 'forge';
        $password = config('database.connections.'.$driver.'.password') ?: '';

        try {
            if ($driver === 'sqlite') {
                return file_exists($database) || is_writable(dirname($database));
            }

            $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";
            new \PDO($dsn, $username, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /** @return AuditCheck[] */
    private function checkTerminalSupport(): array
    {
        return [
            new AuditCheck(
                category: AuditCategory::Terminal,
                nameKey: 'terminal_animations',
                status: function_exists('pcntl_fork') ? AuditStatus::Pass : AuditStatus::Warn,
                messageKey: function_exists('pcntl_fork') ? 'terminal_animations_pass' : 'terminal_animations_fail',
                nameParams: [],
                messageParams: [],
            ),
            new AuditCheck(
                category: AuditCategory::Terminal,
                nameKey: 'terminal_interactive',
                status: function_exists('posix_isatty') ? AuditStatus::Pass : AuditStatus::Warn,
                messageKey: function_exists('posix_isatty') ? 'terminal_interactive_pass' : 'terminal_interactive_fail',
                nameParams: [],
                messageParams: [],
            ),
        ];
    }
}
