<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use Illuminate\Console\Command;

class DeployDetectCommand extends Command
{
    protected $signature = 'deploy:detect
        {--json : Output results as JSON}';

    protected $description = 'Probe server capabilities and recommend a deployment profile';

    public function handle(): int
    {
        $container = $this->probeContainer();
        $redis = $this->probeRedis();
        $daemon = $this->probeDaemon();
        $composer = $this->probeComposer();

        $recommended = ($container || ($redis && $daemon))
            ? 'vps-docker'
            : 'shared-hosting';

        $explicitOverride = env('DEPLOY_PROFILE');
        if ($explicitOverride === 'auto' || empty($explicitOverride)) {
            $explicitOverride = null;
        }

        if ($this->option('json')) {
            $this->output->writeln((string) json_encode([
                'recommended_profile' => $recommended,
                'probes' => [
                    'container' => $container,
                    'redis' => $redis,
                    'daemon' => $daemon,
                    'composer' => $composer,
                ],
                'override' => $explicitOverride,
            ], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info(__('core.deploy.detection_heading', ['default' => 'Server Capability Probes:']));
        $this->line('- Container runtime: '.($container ? 'DETECTED' : 'NOT DETECTED'));
        $this->line('- Redis reachable: '.($redis ? 'YES' : 'NO'));
        $this->line('- Daemon capability (pcntl/posix): '.($daemon ? 'YES' : 'NO'));
        $this->line('- Composer available: '.($composer ? 'YES' : 'NO'));

        $this->newLine();
        $this->info("Recommended Profile: {$recommended}");

        if ($explicitOverride !== null) {
            $this->warn("DEPLOY_PROFILE override is set to: {$explicitOverride} (takes precedence over recommendation)");
        }

        return self::SUCCESS;
    }

    public function probeContainer(): bool
    {
        if (is_file('/.dockerenv')) {
            return true;
        }

        if (file_exists('/proc/1/cgroup')) {
            $cgroup = @file_get_contents('/proc/1/cgroup');
            if ($cgroup !== false && str_contains($cgroup, 'docker')) {
                return true;
            }
        }

        return (bool) env('DOCKER_CONTAINER', false);
    }

    public function probeRedis(): bool
    {
        $host = (string) (config('database.redis.default.host') ?: '127.0.0.1');
        $port = (int) (config('database.redis.default.port') ?: 6379);

        $fp = @fsockopen($host, $port, $errno, $errstr, 0.5);
        if (is_resource($fp)) {
            fclose($fp);

            return true;
        }

        return false;
    }

    public function probeDaemon(): bool
    {
        return extension_loaded('pcntl') && extension_loaded('posix');
    }

    public function probeComposer(): bool
    {
        $output = @shell_exec('which composer 2>/dev/null');

        return ! empty($output);
    }
}
