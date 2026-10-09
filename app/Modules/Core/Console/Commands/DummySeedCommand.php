<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use Database\Seeders\DummySeeder;
use Illuminate\Console\Command;
use Tests\Support\DummyData;

class DummySeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dummy:seed {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with isolated demo and dummy records';

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

        /** @var DummySeeder $seeder */
        $seeder = app(DummySeeder::class);
        $seeder->setCommand($this);
        $seeder->run();

        return self::SUCCESS;
    }
}
