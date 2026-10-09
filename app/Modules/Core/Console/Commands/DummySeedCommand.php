<?php

declare(strict_types=1);

namespace App\Modules\Core\Console\Commands;

use App\Modules\Core\Support\DummyData;
use App\Modules\Setup\Entities\SetupEntity;
use Database\Seeders\DummySeeder;
use Illuminate\Console\Command;

class DummySeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dummy:seed {--force : Force the operation to run}';

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
        if (! SetupEntity::get()->isInstalled()) {
            $this->error(__('dummy.not_installed'));

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
