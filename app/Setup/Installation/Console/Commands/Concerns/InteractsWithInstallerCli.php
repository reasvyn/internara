<?php

declare(strict_types=1);

namespace App\Setup\Installation\Console\Commands\Concerns;

use App\Core\Support\AppInfo;
use App\Setup\Entities\SetupEntity;

trait InteractsWithInstallerCli
{
    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=blue> '.__('setup.cli.banner_title').' </>');
        $this->line(
            '  <fg=blue>'.
                __('setup.cli.banner_subtitle').
                '</> <fg=gray>v'.
                AppInfo::version().
                '</>',
        );
        $this->newLine();

        $this->components->twoColumnDetail(__('setup.cli.php_version'), PHP_VERSION);
        $this->components->twoColumnDetail(__('setup.cli.environment'), app()->environment());
        $this->components->twoColumnDetail(__('setup.cli.timezone'), config('app.timezone'));
    }

    protected function displayCompletion(): void
    {
        $this->newLine();
        $this->line('  <fg=black;bg=green> '.__('setup.cli.installation_completed').' </>');
    }

    protected function displaySection(string $title): void
    {
        $this->newLine();
        $this->line('<fg=white;options=bold>  '.$title.'</>');
    }

    protected function displayError(string $message): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=red> ERROR </>');
        $this->line('  <fg=red>'.$message.'</>');
    }

    protected function isInstalled(): bool
    {
        return SetupEntity::get()->isInstalled();
    }
}
