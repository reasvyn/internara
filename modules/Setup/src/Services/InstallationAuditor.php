<?php

declare(strict_types=1);

namespace Modules\Setup\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Setup\Services\Contracts\InstallationAuditor as Contract;
use Modules\Shared\Services\BaseService;

/**
 * Service implementation for performing technical pre-flight system audits.
 *
 * [S1 - Secure] Sanitizes sensitive database connection information in error logs.
 */
class InstallationAuditor extends BaseService implements Contract
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
        'zip',
    ];

    /**
     * Required PHP functions for system operations.
     */
    protected const PHP_FUNCTIONS = ['proc_open', 'exec', 'shell_exec'];

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
            'functions' => $this->checkFunctions(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequirements(): array
    {
        $results = [
            __('setup::wizard.environment.audit.php_version', [
                'version' => self::MIN_PHP_VERSION,
            ]) => version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>='),
        ];

        foreach (self::PHP_EXTENSIONS as $extension) {
            $label = __('setup::wizard.environment.audit.php_extension', [
                'extension' => strtoupper($extension),
            ]);
            $results[$label] = extension_loaded($extension);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function checkFunctions(): array
    {
        $results = [];

        foreach (self::PHP_FUNCTIONS as $function) {
            $label = __('setup::wizard.environment.audit.php_function', ['function' => $function]);
            $results[$label] =
                function_exists($function) &&
                !in_array($function, explode(',', ini_get('disable_functions')));
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPermissions(): array
    {
        return [
            __('setup::wizard.environment.audit.storage_root') => is_writable(storage_path()),
            __('setup::wizard.environment.audit.storage_logs') => is_writable(storage_path('logs')),
            __('setup::wizard.environment.audit.storage_framework') => is_writable(
                storage_path('framework'),
            ),
            __('setup::wizard.environment.audit.bootstrap_cache') => is_writable(
                base_path('bootstrap/cache'),
            ),
            __('setup::wizard.environment.audit.env_file') => File::exists(base_path('.env'))
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
                'message' => __('setup::wizard.environment.audit.db_connected'),
            ];
        } catch (\Exception $e) {
            $rawMessage = $e->getMessage();
            // [S1 - Secure] Robust sanitization of sensitive data from DB errors
            $sanitizedMessage = preg_replace(
                '/(password|pwd|user|usr|host|address|dsn|credential|token)=[^; ]+/i',
                '$1=****',
                $rawMessage,
            );

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

        $requirementsPassed = !in_array(false, $audit['requirements'], true);
        $permissionsPassed = !in_array(false, $audit['permissions'], true);
        $databasePassed = (bool) $audit['database']['connection'];
        $functionsPassed = !in_array(false, $audit['functions'], true);

        return $requirementsPassed && $permissionsPassed && $databasePassed && $functionsPassed;
    }
}
