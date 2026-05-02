<?php

declare(strict_types=1);

namespace App\Console\Commands\System;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * CLI tool to promote a user to Admin role.
 *
 * S1 - Secure: Traceable administrative action via CLI.
 */
class AdminPromoteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:admin:promote {identifier : Email or Username of the user} {--role=admin : Role to assign (admin/super_admin)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote a user to an administrative role';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $roleName = $this->option('role');

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user) {
            $this->error("User not found with identifier: {$identifier}");

            return Command::FAILURE;
        }

        if (! in_array($roleName, ['admin', 'super_admin'])) {
            $this->error("Invalid role: {$roleName}. Only admin or super_admin are allowed.");

            return Command::FAILURE;
        }

        if (! Role::where('name', $roleName)->exists()) {
            $this->error("Role '{$roleName}' does not exist in the database.");

            return Command::FAILURE;
        }

        if ($user->hasRole($roleName)) {
            $this->warn("User {$user->name} already has the '{$roleName}' role.");

            return Command::SUCCESS;
        }

        $user->assignRole($roleName);

        $this->info("Successfully promoted {$user->name} ({$user->email}) to {$roleName}.");

        \Log::notice("User promoted via CLI: {$user->email} promoted to {$roleName}");

        return Command::SUCCESS;
    }
}
