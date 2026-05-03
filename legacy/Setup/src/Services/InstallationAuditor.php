<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Modules\Setup\Services\Contracts\InstallationAuditor as Contract;

/**
 * Installation Auditor - Pre-flight system checks
 *
 * [S1 - Secure] Sanitizes sensitive data from error messages
 * [S2 - Sustain] Clear pass/fail reporting
 * [S3 - Scalable] Extensible requirement registry
 */
class InstallationAuditor implements Contract
{
    protected const REQUIRED_PHP_VERSION = '8.4.0';

    protected const REQUIRED_EXTENSIONS = [
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

    protected const RECOMMENDED_EXTENSIONS = ['redis', 'pcntl', 'posix'];

    /**
     * {@inheritdoc}
     */
    public function audit(): array
    {
        return [
            'requirements' => $this->checkRequirements(),
            'permissions' => $this->checkPermissions(),
            'database' => $this->checkDatabase(),
            'recommendations' => $this->checkRecommendations(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function passes(): bool
    {
        $audit = $this->audit();

        foreach ($audit['requirements'] as $check) {
            if ($check['status'] === false) {
                return false;
            }
        }

        foreach ($audit['permissions'] as $check) {
            if ($check['status'] === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check PHP version and required extensions
     */
    protected function checkRequirements(): array
    {
        $results = [];

        // PHP version
        $results[] = [
            'name' => 'PHP Version >= '.self::REQUIRED_PHP_VERSION,
            'status' => version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '>='),
            'value' => PHP_VERSION,
            'required' => self::REQUIRED_PHP_VERSION,
        ];

        // Required extensions
        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $loaded = extension_loaded($extension);
            $results[] = [
                'name' => "PHP Extension: {$extension}",
                'status' => $loaded,
                'value' => $loaded ? 'Loaded' : 'Missing',
                'required' => 'Loaded',
            ];
        }

        return $results;
    }

    /**
     * Check file permissions
     */
    protected function checkPermissions(): array
    {
        $results = [];
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            'database' => database_path(),
        ];

        foreach ($paths as $name => $path) {
            $writable = File::isWritable($path);
            $results[] = [
                'name' => "Directory writable: {$name}",
                'status' => $writable,
                'path' => $path,
                'value' => $writable ? 'Writable' : 'Not writable',
            ];
        }

        return $results;
    }

    /**
     * Check database connectivity (without exposing credentials)
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                [
                    'name' => 'Database connection',
                    'status' => true,
                    'value' => 'Connected',
                    'driver' => config('database.default'),
                ],
            ];
        } catch (\Exception $e) {
            $sanitizedMessage = $this->sanitizeErrorMessage($e->getMessage());

            return [
                [
                    'name' => 'Database connection',
                    'status' => false,
                    'value' => $sanitizedMessage,
                    'driver' => config('database.default'),
                ],
            ];
        }
    }

    /**
     * Check recommended extensions (non-blocking)
     */
    protected function checkRecommendations(): array
    {
        $results = [];

        foreach (self::RECOMMENDED_EXTENSIONS as $extension) {
            $loaded = extension_loaded($extension);
            $results[] = [
                'name' => "Recommended: {$extension}",
                'status' => $loaded,
                'value' => $loaded ? 'Loaded' : 'Not loaded',
                'required' => false,
            ];
        }

        return $results;
    }

    /**
     * [S1 - Secure] Sanitize sensitive data from error messages
     */
    protected function sanitizeErrorMessage(string $rawMessage): string
    {
        return preg_replace(
            '/(password|pwd|user|usr|host|address|dsn|credential|token)=[^;\s]+/i',
            '$1=****',
            $rawMessage,
        );
    }
}
