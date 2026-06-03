<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\Admin\Aggregates\Setup\Models\Setup;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ShowRecoveryKeyCommand extends Command
{
    protected $signature = 'admin:recovery-show';

    protected $description = 'Display the recovery key from the stored file';

    public function handle(): int
    {
        $path = storage_path('app/private/.recovery-key');

        if (! File::exists($path)) {
            $this->components->error(__('admin.recovery_path.missing'));

            return self::FAILURE;
        }

        $setup = Setup::latest('created_at')->first();

        if (! $setup || ! $setup->recovery_key) {
            $this->components->warn(__('admin.recovery_show.no_setup'));

            return self::FAILURE;
        }

        $this->components->warn(__('admin.recovery_show.warning'));

        $confirmed = $this->components->confirm(
            question: __('admin.recovery_show.confirm'),
            default: false,
        );

        if (! $confirmed) {
            $this->components->info(__('admin.recovery_show.aborted'));

            return self::SUCCESS;
        }

        $content = File::get($path);

        SmartLogger::info('Recovery key viewed via CLI')
            ->module('admin')
            ->event('recovery_key.viewed')
            ->systemOnly()
            ->save();

        $this->newLine();
        $this->line($content);

        return self::SUCCESS;
    }
}
