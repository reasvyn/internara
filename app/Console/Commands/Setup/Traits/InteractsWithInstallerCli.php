<?php

declare(strict_types=1);

namespace App\Console\Commands\Setup\Traits;

use App\Support\AppInfo;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

/**
 * Trait to provide standardized CLI output for Internara Setup commands.
 */
trait InteractsWithInstallerCli
{
    /**
     * Display the standard installer banner.
     */
    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=blue> '.__('setup.cli.banner_title').' </>');
        $this->line('  <fg=blue>'.__('setup.cli.banner_subtitle').'</> <fg=gray>v'.AppInfo::version().'</>');
        $this->newLine();

        intro(__('setup.cli.banner_title').' (v'.AppInfo::version().')');
    }

    /**
     * Display a success completion message.
     */
    protected function displayCompletion(?string $message = null): void
    {
        $message ??= __('setup.cli.installation_completed');
        $this->newLine();
        outro($message);
    }

    /**
     * Format a two-column detail for the CLI.
     */
    protected function detail(string $label, string $value): void
    {
        $this->components->twoColumnDetail($label, $value);
    }
}
