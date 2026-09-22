<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeployConfigureCommand extends Command
{
    protected $signature = 'deploy:configure
        {--profile= : Deployment profile: shared-hosting|vps-docker (defaults to resolved profile)}
        {--env-path= : Optional path to .env file for testing}';

    protected $description = 'Apply deployment profile preset drivers to .env configuration';

    public function handle(): int
    {
        $profiles = config('deployment.profiles', []);
        $profile = $this->option('profile');

        if (empty($profile)) {
            $explicit = env('DEPLOY_PROFILE');
            if (! empty($explicit) && $explicit !== 'auto') {
                $profile = $explicit;
            } else {
                $detect = new DeployDetectCommand;
                $container = $detect->probeContainer();
                $redis = $detect->probeRedis();
                $daemon = $detect->probeDaemon();

                $profile = ($container || ($redis && $daemon))
                    ? 'vps-docker'
                    : (config('deployment.default') ?? 'shared-hosting');
            }
        }

        if (! array_key_exists($profile, $profiles)) {
            $this->error("Invalid deployment profile: {$profile}. Allowed profiles: ".implode(', ', array_keys($profiles)));

            return self::FAILURE;
        }

        $preset = $profiles[$profile];
        $envPath = (string) ($this->option('env-path') ?: base_path('.env'));

        if (! File::exists($envPath)) {
            $examplePath = base_path('.env.example');
            if (File::exists($examplePath)) {
                File::copy($examplePath, $envPath);
            } else {
                File::put($envPath, '');
            }
        }

        $envContent = File::get($envPath);

        // Drivers allowlist ONLY — NEVER touch secrets (APP_KEY, DB_PASSWORD, MAIL_PASSWORD)
        $driverUpdates = [
            'QUEUE_CONNECTION' => $preset['queue'] ?? 'sync',
            'CACHE_STORE' => $preset['cache'] ?? 'file',
            'SESSION_DRIVER' => $preset['session'] ?? 'database',
            'BROADCAST_CONNECTION' => $preset['broadcast'] ?? 'log',
        ];

        foreach ($driverUpdates as $key => $val) {
            $pattern = "/^{$key}=.*/m";
            $newLine = "{$key}={$val}";

            if (preg_match($pattern, $envContent)) {
                $envContent = (string) preg_replace($pattern, $newLine, $envContent);
            } else {
                $envContent .= "\n{$newLine}";
            }
        }

        File::put($envPath, $envContent);

        $this->info("Successfully applied [{$profile}] preset to .env:");
        foreach ($driverUpdates as $key => $val) {
            $this->line("  {$key}={$val}");
        }

        return self::SUCCESS;
    }
}
