<?php

declare(strict_types=1);

namespace App\Setup\Installation\Actions;

use App\Core\Actions\BaseProcessAction;
use Database\Seeders\DummySeeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Seeds the demo dataset (DummySeeder) on demand after a fresh install.
 *
 * Invoked by `setup:install --with-dummy` (installation spec FR-C10). Refuses to
 * run in production — the seed is skipped with a warning while the installation
 * continues (NFR-S13). DummySeeder keeps its own independent production guard
 * (dummy-data spec NFR-S1) as defense in depth (installation spec DD-10).
 */
final class SeedDummyDataAction extends BaseProcessAction
{
    public function execute(): bool
    {
        if (app()->environment('production')) {
            $this->log('dummy_data_skipped', null, ['reason' => 'production']);

            return false;
        }

        $exitCode = Artisan::call('db:seed', ['--class' => DummySeeder::class]);

        if ($exitCode !== 0) {
            $this->fail('Dummy data seeding failed.');
        }

        $this->log('dummy_data_seeded');

        return true;
    }
}
