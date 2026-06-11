<?php

declare(strict_types=1);

namespace App\User\UserManagement\Console\Commands;

use App\Auth\Permissions\Enums\Role;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use App\User\UserManagement\Actions\SetUserStatusAction;
use Illuminate\Console\Command;

class AutoInactivateAccounts extends Command
{
    protected $signature = 'accounts:auto-inactivate
        {--days=90 : Number of days since last activity before marking inactive}
        {--dry-run : List accounts that would be inactivated without making changes}';

    protected $description = 'Transition VERIFIED accounts to INACTIVE after extended inactivity';

    public function __construct(
        private SetUserStatusAction $setStatus,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $threshold = now()->subDays((int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', Role::SUPER_ADMIN->value))
            ->where('status', AccountStatus::VERIFIED->value)
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<', $threshold);
            })
            ->get();

        if ($users->isEmpty()) {
            $this->components->info(__('user.user_management.auto_inactivate.none_found'));

            return self::SUCCESS;
        }

        $this->components->info(
            __('user.user_management.auto_inactivate.found', [
                'count' => $users->count(),
                'days' => $this->option('days'),
            ]),
        );

        if ($dryRun) {
            foreach ($users as $user) {
                $this->line(
                    '  [DRY-RUN] '.
                        __('user.user_management.auto_inactivate.dry_run', [
                            'email' => $user->email,
                            'name' => $user->name,
                        ]),
                );
            }

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $this->setStatus->execute(
                $user,
                AccountStatus::INACTIVE,
                __('user.user_management.auto_inactivate.reason'),
                skipAuthCheck: true,
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->components->info(
            __('user.user_management.auto_inactivate.completed', ['count' => $users->count()]),
        );

        return self::SUCCESS;
    }
}
