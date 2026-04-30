<?php

declare(strict_types=1);

namespace Modules\Core\Metadata\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Modules\Core\Metadata\Services\Contracts\MetadataService;

/**
 * Class AppInfoCommand
 *
 * Displays technical identity and environment information about the Internara application.
 */
class AppInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:info';

    /**
     * The console command description.
     */
    protected $description = 'Display application identity, version, and tech stack';

    /**
     * Execute the console command.
     */
    public function handle(MetadataService $metadata): int
    {
        $info = $metadata->getAll();
        $composer = $this->getComposerData();
        $package = $this->getPackageData();

        $this->newLine();
        $this->components->info('Internara Application Information');

        $this->components->twoColumnDetail(
            'Application Name',
            (string) $metadata->get('name', config('app.name', 'Unknown')),
        );
        $this->components->twoColumnDetail('Version', $metadata->getVersion());
        $this->components->twoColumnDetail(
            'Support',
            (string) $metadata->get('support', 'Unknown'),
        );

        $this->newLine();
        $this->components->info('Main Tech Stacks');
        $this->components->twoColumnDetail(
            'Laravel Framework',
            $composer['require']['laravel/framework'] ?? 'v12.x',
        );
        $this->components->twoColumnDetail(
            'Livewire',
            $composer['require']['livewire/livewire'] ?? 'v3.x',
        );
        $this->components->twoColumnDetail(
            'MaryUI',
            $composer['require']['robsontenorio/mary'] ?? 'v2.x',
        );
        $this->components->twoColumnDetail(
            'Tailwind CSS',
            $package['devDependencies']['tailwindcss'] ?? 'v4.x',
        );
        $this->components->twoColumnDetail(
            'DaisyUI',
            $package['devDependencies']['daisyui'] ?? 'v5.x',
        );
        $this->components->twoColumnDetail('Alpine.js', 'Bundled (Livewire 3)');
        $this->components->twoColumnDetail(
            'Pest PHP',
            $composer['require-dev']['pestphp/pest'] ?? 'v4.x',
        );

        $this->newLine();
        $this->components->info('Author Information');
        $author = $metadata->getAuthor();
        $this->components->twoColumnDetail('Author', (string) ($author['name'] ?? 'Unknown'));
        $this->components->twoColumnDetail('GitHub', (string) ($author['github'] ?? 'Unknown'));
        $this->components->twoColumnDetail('Email', (string) ($author['email'] ?? 'Unknown'));

        $this->newLine();
        $this->components->info('Environment Details');
        $this->components->twoColumnDetail('Laravel Version', App::version());
        $this->components->twoColumnDetail('PHP Version', PHP_VERSION);
        $this->components->twoColumnDetail('Environment', (string) App::environment());
        $this->components->twoColumnDetail(
            'Debug Mode',
            is_debug_mode() ? '<fg=yellow>Enabled</>' : '<fg=green>Disabled</>',
        );
        $this->components->twoColumnDetail(
            'Database Driver',
            (string) config('database.default', 'Unknown'),
        );

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Get data from composer.json.
     */
    protected function getComposerData(): array
    {
        $path = base_path('composer.json');

        return File::exists($path) ? json_decode(File::get($path), true) : [];
    }

    /**
     * Get data from package.json.
     */
    protected function getPackageData(): array
    {
        $path = base_path('package.json');

        return File::exists($path) ? json_decode(File::get($path), true) : [];
    }
}
