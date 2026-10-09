<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Tests\Support\DummyData;

class DummyRollbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dummy:rollback {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback and purge all isolated demo and dummy records from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error(__('dummy.production_warning'));

            return self::FAILURE;
        }

        if (! class_exists(DummyData::class)) {
            $this->error(__('dummy.helper_missing'));

            return self::FAILURE;
        }

        $this->info(__('dummy.rollback_title'));
        $this->info(__('dummy.rollback_starting'));

        $purged = DummyData::rollback();
        $totalPurged = array_sum($purged);

        if ($totalPurged === 0) {
            $this->newLine();
            $this->comment(__('dummy.rollback_none'));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info(__('dummy.rollback_complete'));
        $this->info(__('dummy.rollback_summary_header'));

        foreach ($purged as $key => $count) {
            if ($count > 0) {
                $label = __("dummy.entities.{$key}");
                $this->line("  {$label}: {$count}");
            }
        }

        return self::SUCCESS;
    }
}
