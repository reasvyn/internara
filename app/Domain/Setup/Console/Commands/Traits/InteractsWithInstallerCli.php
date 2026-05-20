<?php

declare(strict_types=1);

namespace App\Domain\Setup\Console\Commands\Traits;

use App\Domain\Settings\Support\AppInfo;
use App\Domain\Setup\Models\Setup;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

trait InteractsWithInstallerCli
{
    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=blue> '.__('setup.cli.banner_title').' </>');
        $this->line('  <fg=blue>'.__('setup.cli.banner_subtitle').'</> <fg=gray>v'.AppInfo::version().'</>');
        $this->newLine();

        $this->components->twoColumnDetail(__('setup.cli.php_version'), PHP_VERSION);
        $this->components->twoColumnDetail(__('setup.cli.environment'), app()->environment());
        $this->components->twoColumnDetail(__('setup.cli.timezone'), config('app.timezone'));
        $this->newLine();

        intro(__('setup.cli.banner_title').' (v'.AppInfo::version().')');
    }

    protected function displayCompletion(?string $message = null): void
    {
        $message ??= __('setup.cli.installation_completed');
        $this->newLine();
        outro($message);
    }

    protected function isInstalled(): bool
    {
        return Setup::state()->isInstalled();
    }
}
