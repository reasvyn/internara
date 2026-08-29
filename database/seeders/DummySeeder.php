<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Settings\Models\Setting;
use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\Support\DummyData;

/**
 * Opt-in entry point for the factory-driven demo dataset.
 *
 * Implements docs/specs/3UOZP-dummy-data.md §6.1: refuses to run in production (NFR-S1),
 * reuses base-seeded roles/settings/active academic year (FR-E4, FR-H14), delegates
 * all generation to Tests\Support\DummyData (FR-E2), and prints a bilingual per-entity
 * summary via __() (FR-E5, NFR-U1). Never registered in DatabaseSeeder or SetupSeeder
 * (FR-E3, DD-3).
 */
class DummySeeder extends Seeder
{
    public function run(): void
    {
        // NFR-S1 — refuse to run in production.
        if (app()->environment('production')) {
            throw new RuntimeException(__('dummy.production_guard'));
        }

        // FR-E4 — call base seeders only when their data is absent.
        $this->seedBaseDataWhenAbsent();

        if ($this->command !== null) {
            $this->command->info(__('dummy.title'));
            $this->command->info(__('dummy.starting'));
        }

        // FR-E2 — delegate all generation to the dev-only helper.
        $counts = DummyData::make()->run();

        $this->printSummary($counts);
        $this->printDemoAccounts();
    }

    /**
     * FR-E4 — reuse base-seeded data; seed roles/settings/years only when missing.
     */
    private function seedBaseDataWhenAbsent(): void
    {
        if (! Role::query()->exists()) {
            $this->call(RolePermissionSeeder::class);
        }

        if (! Setting::query()->exists()) {
            $this->call(AppSettingSeeder::class);
        }

        if (! AcademicYear::query()->exists()) {
            $this->call(AcademicYearSeeder::class);
        }
    }

    /**
     * FR-E5 — print the per-entity creation summary via __().
     *
     * @param array<string, int> $counts
     */
    private function printSummary(array $counts): void
    {
        if ($this->command === null) {
            return;
        }

        $this->command->newLine();
        $this->command->info(__('dummy.complete'));
        $this->command->info(__('dummy.summary_header'));

        foreach ($counts as $key => $count) {
            $this->command->line('  '.__("dummy.entities.{$key}").': '.$count);
        }
    }

    /**
     * UC-2 / §6.3 — list the deterministic demo accounts.
     */
    private function printDemoAccounts(): void
    {
        if ($this->command === null) {
            return;
        }

        $this->command->newLine();
        $this->command->info(__('dummy.demo_accounts', ['password' => config('dummy.password')]));

        foreach (DummyData::demoAccounts() as $email) {
            $this->command->line('  '.$email);
        }
    }
}
