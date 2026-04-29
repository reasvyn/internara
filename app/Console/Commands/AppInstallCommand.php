<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Setup\InstallSystemAction;
use App\Support\AppInfo;
use Illuminate\Console\Command;
use Throwable;

class AppInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:install {--force : Force the installation}';

    /**
     * The console command description.
     */
    protected $description = 'Install and initialize the Internara system';

    /**
     * Execute the console command.
     */
    public function handle(InstallSystemAction $installSystem): int
    {
        $this->displayBanner();

        if (!$this->confirmInstallation()) {
            $this->warn('Installation aborted by user.');
            return self::FAILURE;
        }

        try {
            $this->components->task('Installing system components...', function () use ($installSystem) {
                $installSystem->execute();
            });

            $this->newLine();
            $this->components->info('Internara has been successfully installed!');
            $this->line("Version: <fg=cyan>" . AppInfo::version() . "</>");
            
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->components->error('Installation Failed: ' . $e->getMessage());
            
            if ($this->getOutput()->isVerbose()) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line(' <fg=white;bg=blue;options=bold> INTERNARA </> <fg=blue;options=bold>SYSTEM INSTALLER</>');
        $this->line(' <fg=gray>Version: ' . AppInfo::version() . '</>');
        $this->newLine();
    }

    protected function confirmInstallation(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return $this->confirm('This will RESET your database and delete all existing data. Do you want to proceed?', false);
    }
}
