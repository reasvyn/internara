<?php

declare(strict_types=1);

namespace App\Modules\Setup\Domain\Installation\Actions;

use App\Modules\Core\Actions\BaseProcessAction;
use Database\Seeders\DummySeeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Seeds the demo dataset (DummySeeder) on demand after a fresh install.
 *
 * Invoked by `setup:install --with-dummy` (installation spec FR-C10).
 */
final class SeedDummyDataAction extends BaseProcessAction
{
    public function execute(): bool
    {
        $exitCode = Artisan::call('db:seed', ['--class' => DummySeeder::class]);

        if ($exitCode !== 0) {
            $this->fail('Dummy data seeding failed.');
        }

        $this->log('dummy_data_seeded');

        return true;
    }
}
