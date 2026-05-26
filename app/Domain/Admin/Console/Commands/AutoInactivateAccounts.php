<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoInactivateAccounts extends Command
{
    protected $signature = 'accounts:auto-inactivate
        {--days=90 : Number of days since last activity before marking inactive}
        {--dry-run : List accounts that would be inactivated without making changes}';

    protected $description = 'Transition VERIFIED accounts to INACTIVE after extended inactivity';

    public function __construct(
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $threshold = Carbon::now()->subDays((int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', Role::SUPER_ADMIN->value))
            ->whereHas('statuses', fn ($q) => $q->where('name', AccountStatus::VERIFIED->value))
            ->whereDoesntHave('statuses', fn ($q) => $q->whereIn('name', [
                AccountStatus::INACTIVE->value,
                AccountStatus::ARCHIVED->value,
                AccountStatus::PROTECTED->value,
            ]))
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<', $threshold);
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('No inactive accounts found.');

            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} accounts inactive for more than {$this->option('days')} days.");

        if ($dryRun) {
            foreach ($users as $user) {
                $this->line("  [DRY-RUN] Would inactivate: {$user->email} ({$user->name})");
            }

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $user->setStatus(AccountStatus::INACTIVE->value);

            SmartLogger::info('account_auto_inactivated')
                ->withPayload(['days_inactive' => (int) $this->option('days')])
                ->event('account_auto_inactivated')
                ->module('Auth')
                ->activityOnly()
                ->save();

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Inactivated {$users->count()} accounts.");

        return self::SUCCESS;
    }
}
