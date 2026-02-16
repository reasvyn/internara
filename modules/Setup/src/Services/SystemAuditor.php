<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Setup\Services\Contracts\SystemAuditor as SystemAuditorContract;

/**
 * Service implementation for performing pre-flight system audits.
 */
class SystemAuditor implements SystemAuditorContract
{
    /**
     * Required PHP extensions.
     */
    protected const PHP_EXTENSIONS = [
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
    ];

    /**
     * Minimum PHP version.
     */
    protected const MIN_PHP_VERSION = '8.2.0';

    /**
     * {@inheritdoc}
     */
    public function audit(): array
    {
        return [
            'requirements' => $this->checkRequirements(),
            'permissions' => $this->checkPermissions(),
            'database' => $this->checkDatabase(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements(): array
    {
        $results = [
            'php_version' => version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>='),
        ];

        foreach (self::PHP_EXTENSIONS as $extension) {
            $results["extension_{$extension}"] = extension_loaded($extension);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPermissions(): array
    {
        return [
            'storage_directory' => is_writable(storage_path()),
            'storage_logs' => is_writable(storage_path('logs')),
            'storage_framework' => is_writable(storage_path('framework')),
            'bootstrap_cache' => is_writable(base_path('bootstrap/cache')),
            'env_file' => File::exists(base_path('.env'))
                ? is_writable(base_path('.env'))
                : is_writable(base_path()),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'connection' => true,
                'message' => 'Database connection established.',
            ];
        } catch (\Exception $e) {
            return [
                'connection' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function passes(): bool
    {
        $audit = $this->audit();

        $requirementsPassed = !in_array(false, $audit['requirements'], true);
        $permissionsPassed = !in_array(false, $audit['permissions'], true);
        $databasePassed = (bool) $audit['database']['connection'];

        return $requirementsPassed && $permissionsPassed && $databasePassed;
    }
}
