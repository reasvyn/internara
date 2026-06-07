<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Services;

use App\Data\AuditCheck;
use App\Data\AuditReport;
use App\Enums\AuditCategory;
use App\Enums\AuditStatus;

class EnvironmentAuditor
{
    public function audit(): AuditReport
    {
        return new AuditReport([
            ...$this->checkPhpVersion(),
            ...$this->checkExtensions(config('setup.requirements.extensions'), critical: true),
            ...$this->checkExtensions(
                config('setup.requirements.recommended_extensions'),
                critical: false,
            ),
            ...$this->checkPermissions(),
            ...$this->checkDatabaseConnection(),
            ...$this->checkTerminalSupport(),
            ...$this->checkFrontendAssets(),
        ]);
    }

    /** @return AuditCheck[] */
    private function checkPhpVersion(): array
    {
        $required = config('setup.requirements.php_version');
        $pass = version_compare(PHP_VERSION, $required, '>=');

        return [
            new AuditCheck(
                category: AuditCategory::REQUIREMENTS,
                nameKey: 'php_version',
                status: $pass ? AuditStatus::PASS : AuditStatus::FAIL,
                messageKey: $pass ? 'php_version_pass' : 'php_version_fail',
                nameParams: ['required' => $required],
                messageParams: ['current' => PHP_VERSION, 'required' => $required],
            ),
        ];
    }

    /** @return AuditCheck[] */
    private function checkExtensions(array $extensions, bool $critical): array
    {
        $checks = [];

        foreach ($extensions as $extension) {
            $loaded = extension_loaded($extension);
            $failStatus = $critical ? AuditStatus::FAIL : AuditStatus::WARN;

            $checks[] = new AuditCheck(
                category: $critical ? AuditCategory::REQUIREMENTS : AuditCategory::RECOMMENDATIONS,
                nameKey: $critical ? 'extension' : 'recommended_extension',
                status: $loaded ? AuditStatus::PASS : $failStatus,
                messageKey: $loaded
                    ? ($critical
                        ? 'extension_pass'
                        : 'recommended_pass')
                    : ($critical
                        ? 'extension_fail'
                        : 'recommended_fail'),
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
                category: AuditCategory::PERMISSIONS,
                nameKey: 'writable_dir',
                status: $writable ? AuditStatus::PASS : AuditStatus::FAIL,
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

        return [
            new AuditCheck(
                category: AuditCategory::DATABASE,
                nameKey: 'db_connection',
                status: $connected ? AuditStatus::PASS : AuditStatus::FAIL,
                messageKey: $connected ? 'db_pass' : 'db_fail',
                nameParams: ['driver' => $driver],
                messageParams: ['driver' => $driver],
            ),
        ];
    }

    /** @return AuditCheck[] */
    private function testDatabaseConnection(string $driver): bool
    {
        $host = config('database.connections.'.$driver.'.host', '');
        $port = config('database.connections.'.$driver.'.port', '');
        $database = config('database.connections.'.$driver.'.database', '');
        $username = config('database.connections.'.$driver.'.username', '');
        $password = config('database.connections.'.$driver.'.password', '');

        if ($username === 'forge' || $database === 'forge') {
            return false;
        }

        try {
            if ($driver === 'sqlite') {
                $dbPath = str_starts_with($database, '/') ? $database : base_path($database);

                return file_exists($dbPath) || is_writable(dirname($dbPath));
            }

            $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";
            new \PDO($dsn, $username, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return AuditCheck[] */
    private function checkTerminalSupport(): array
    {
        return [
            new AuditCheck(
                category: AuditCategory::TERMINAL,
                nameKey: 'terminal_animations',
                status: function_exists('pcntl_fork') ? AuditStatus::PASS : AuditStatus::WARN,
                messageKey: function_exists('pcntl_fork')
                    ? 'terminal_animations_pass'
                    : 'terminal_animations_fail',
                nameParams: [],
                messageParams: [],
            ),
            new AuditCheck(
                category: AuditCategory::TERMINAL,
                nameKey: 'terminal_interactive',
                status: function_exists('posix_isatty') ? AuditStatus::PASS : AuditStatus::WARN,
                messageKey: function_exists('posix_isatty')
                    ? 'terminal_interactive_pass'
                    : 'terminal_interactive_fail',
                nameParams: [],
                messageParams: [],
            ),
        ];
    }

    /** @return AuditCheck[] */
    private function checkFrontendAssets(): array
    {
        $manifestPath = public_path('build/manifest.json');
        $built = file_exists($manifestPath);

        return [
            new AuditCheck(
                category: AuditCategory::RECOMMENDATIONS,
                nameKey: 'frontend_assets',
                status: $built ? AuditStatus::PASS : AuditStatus::WARN,
                messageKey: $built ? 'frontend_assets_pass' : 'frontend_assets_fail',
                nameParams: [],
                messageParams: [],
            ),
        ];
    }
}
