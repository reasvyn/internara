<?php

declare(strict_types=1);

namespace Tests\Quality;

use PHPUnit\Framework\TestCase;

/**
 * S2 - Sustain & S3 - Scalable: Code Stability Tests
 * Ensures code quality metrics are maintained
 */
class CodeStabilityTest extends TestCase
{
    /**
     * S2: Check for hardcoded paths that reduce portability
     */
    public function test_no_hardcoded_paths_in_app(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app'),
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());

            // Check for hardcoded storage paths
            if (preg_match('/[\'"]\/ (?:var|tmp|home|Users)[\/]/', $content)) {
                $violations[] = $relativePath.': Hardcoded absolute path detected';
            }

            // Check for hardcoded database credentials
            if (preg_match('/mysql_connect|mysqli_connect/', $content)) {
                $violations[] =
                    $relativePath.': Direct database connection detected (use Laravel DB facade)';
            }
        }

        $this->assertEmpty($violations, "Hardcoded paths found:\n".implode("\n", $violations));
    }

    /**
     * S1: Check for potential SQL injection vulnerabilities
     */
    public function test_no_raw_sql_in_models(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app/Models'),
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());

            // Check for raw SQL queries (potential SQL injection)
            if (
                preg_match('/DB::raw\s*\(/', $content) ||
                preg_match('/\->select\s*\(DB::raw/', $content)
            ) {
                $violations[] =
                    $relativePath.
                    ': Raw SQL query detected (use Eloquent or parameterized queries)';
            }
        }

        $this->assertEmpty($violations, "Raw SQL found in Models:\n".implode("\n", $violations));
    }

    /**
     * S2: Check for proper error handling (no silent failures)
     */
    public function test_no_silent_catch_blocks(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app'),
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());

            // Check for empty catch blocks or catch with only comments
            if (preg_match('/catch\s*\([^)]+\)\s*\{\s*(?:\/\/.*\s*)*\s*\}/', $content)) {
                $violations[] =
                    $relativePath.': Empty catch block detected (handle or log the error)';
            }
        }

        $this->assertEmpty(
            $violations,
            "Silent catch blocks found:\n".implode("\n", $violations),
        );
    }

    /**
     * S3: Check for proper use of environment variables
     */
    public function test_env_usage_in_config_only(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app'),
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());

            // Allow env() in config files and .env files
            if (
                str_starts_with($relativePath, 'config/') ||
                str_starts_with($relativePath, '.env')
            ) {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // Check for direct env() calls outside config
            if (preg_match('/env\s*\(/', $content)) {
                $violations[] =
                    $relativePath.': Direct env() call detected (use config() instead)';
            }
        }

        $this->assertEmpty(
            $violations,
            "env() usage outside config found:\n".implode("\n", $violations),
        );
    }

    /**
     * S2: Check for deprecated Laravel methods
     */
    public function test_no_deprecated_laravel_methods(): void
    {
        $deprecatedPatterns = [
            '/->whereNotExists\(/',
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app'),
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());

            // Skip Livewire files (they may use magic methods)
            if (str_starts_with($relativePath, 'app/Livewire/')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            foreach ($deprecatedPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $violations[] = $relativePath.': Potential deprecated method usage detected';
                }
            }
        }

        $this->assertEmpty($violations, "Deprecated methods found:\n".implode("\n", $violations));
    }
}
