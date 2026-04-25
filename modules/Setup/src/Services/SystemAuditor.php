<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Setup\Services\Contracts\SystemAuditor as SystemAuditorContract;
use Modules\Shared\Services\BaseService;

/**
 * Service implementation for performing pre-flight system audits.
 */
class SystemAuditor extends BaseService implements SystemAuditorContract
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
    protected const MIN_PHP_VERSION = '8.4.0';

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
            'PHP Version (>= ' . self::MIN_PHP_VERSION . ')' => version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>='),
        ];

        foreach (self::PHP_EXTENSIONS as $extension) {
            $label = 'PHP Extension: ' . strtoupper($extension);
            $results[$label] = extension_loaded($extension);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPermissions(): array
    {
        return [
            'Root Storage Directory' => is_writable(storage_path()),
            'Storage Logs Directory' => is_writable(storage_path('logs')),
            'Storage Framework Directory' => is_writable(storage_path('framework')),
            'Bootstrap Cache Directory' => is_writable(base_path('bootstrap/cache')),
            'Environment File (.env)' => File::exists(base_path('.env'))
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
            $rawMessage = $e->getMessage();
            // [S1 - Secure] Sanitize sensitive data from DB errors (IPs, Usernames, Passwords)
            $sanitizedMessage = preg_replace('/(password|pwd|user|usr|host|address)=[^; ]+/i', '$1=****', $rawMessage);
            
            return [
                'connection' => false,
                'message' => $sanitizedMessage,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function passes(): bool
    {
        $audit = $this->audit();

        $requirementsPassed = ! in_array(false, $audit['requirements'], true);
        $permissionsPassed = ! in_array(false, $audit['permissions'], true);
        $databasePassed = (bool) $audit['database']['connection'];

        return $requirementsPassed && $permissionsPassed && $databasePassed;
    }
}
