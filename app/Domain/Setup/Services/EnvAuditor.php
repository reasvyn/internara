<?php

declare(strict_types=1);

namespace App\Domain\Setup\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Environment Auditor - Pre-flight system checks.
 *
 * [S1 - Secure] Sanitizes sensitive data from error messages.
 * [S2 - Sustain] Clear pass/fail reporting via categories.
 */
class EnvAuditor
{
    protected const string REQUIRED_PHP_VERSION = '8.4.0';

    protected const array REQUIRED_EXTENSIONS = [
        'bcmath', 'ctype', 'fileinfo', 'mbstring', 'openssl', 'pdo',
        'tokenizer', 'xml', 'curl', 'gd', 'intl', 'zip',
    ];

    protected const array RECOMMENDED_EXTENSIONS = ['redis', 'pcntl', 'posix'];

    /**
     * Audit system requirements.
     */
    public function audit(): array
    {
        $categories = [
            'requirements' => [
                'label' => 'System Requirements',
                'checks' => $this->checkRequirements(),
            ],
            'permissions' => [
                'label' => 'File Permissions',
                'checks' => $this->checkPermissions(),
            ],
            'database' => [
                'label' => 'Database Configuration',
                'checks' => $this->checkDatabase(),
            ],
            'recommendations' => [
                'label' => 'Recommendations',
                'checks' => $this->checkRecommendations(),
            ],
        ];

        $passed = true;
        foreach (['requirements', 'permissions', 'database'] as $key) {
            foreach ($categories[$key]['checks'] as $check) {
                if ($check['status'] === 'fail') {
                    $passed = false;
                    break 2;
                }
            }
        }

        return [
            'passed' => $passed,
            'categories' => $categories,
        ];
    }

    /**
     * Check PHP version and required extensions.
     */
    protected function checkRequirements(): array
    {
        $results = [];

        $status = version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '>=');
        $results[] = [
            'name' => 'PHP Version',
            'status' => $status ? 'pass' : 'fail',
            'message' => $status ? 'PHP '.PHP_VERSION : 'Required: >= '.self::REQUIRED_PHP_VERSION,
        ];

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $results[] = [
                'name' => "Extension: {$ext}",
                'status' => $loaded ? 'pass' : 'fail',
                'message' => $loaded ? 'Loaded' : 'Missing',
            ];
        }

        return $results;
    }

    /**
     * Check file permissions.
     */
    protected function checkPermissions(): array
    {
        $results = [];
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        foreach ($paths as $name => $path) {
            $writable = File::exists($path) && File::isWritable($path);
            $results[] = [
                'name' => "Writable: {$name}",
                'status' => $writable ? 'pass' : 'fail',
                'message' => $writable ? 'OK' : 'Not writable',
            ];
        }

        return $results;
    }

    /**
     * Check database connectivity.
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [[
                'name' => 'Connection',
                'status' => 'pass',
                'message' => 'Successfully connected to database',
            ]];
        } catch (\Exception $e) {
            return [[
                'name' => 'Connection',
                'status' => 'fail',
                'message' => $this->sanitizeError($e->getMessage()),
            ]];
        }
    }

    /**
     * Check recommended extensions.
     */
    protected function checkRecommendations(): array
    {
        $results = [];
        foreach (self::RECOMMENDED_EXTENSIONS as $ext) {
            $loaded = extension_loaded($ext);
            $results[] = [
                'name' => "Recommended: {$ext}",
                'status' => $loaded ? 'pass' : 'warn',
                'message' => $loaded ? 'Loaded' : 'Not loaded (optional)',
            ];
        }

        return $results;
    }

    /**
     * Sanitize sensitive data from errors.
     */
    protected function sanitizeError(string $message): string
    {
        return preg_replace('/user\s+\'[^\']+\'/i', "user '[redacted]'", $message);
    }
}
