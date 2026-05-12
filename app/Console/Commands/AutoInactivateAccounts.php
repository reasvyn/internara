<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Core\LogAuditAction;
use App\Enums\Auth\AccountStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoInactivateAccounts extends Command
{
    protected $signature = 'accounts:auto-inactivate
        {--days=90 : Number of days since last activity before marking inactive}
        {--dry-run : List accounts that would be inactivated without making changes}';

    protected $description = 'Transition VERIFIED accounts to INACTIVE after extended inactivity';

    public function __construct(
        private readonly LogAuditAction $logAudit,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $threshold = Carbon::now()->subDays((int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $users = User::query()
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

            $this->logAudit->execute(
                action: 'account_auto_inactivated',
                subjectType: User::class,
                subjectId: $user->id,
                payload: ['days_inactive' => $this->option('days')],
                module: 'Auth',
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Inactivated {$users->count()} accounts.");

        return self::SUCCESS;
    }
}
