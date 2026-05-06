<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Core\LogAuditAction;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Setup\Data\InitializeSuperAdminData;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class InitializeSuperAdminAction
{
    public function __construct(
        private LogAuditAction $logAudit,
    ) {}

    public function execute(InitializeSuperAdminData $data): User
    {
        return DB::transaction(function () use ($data) {
            $name = $data->name ?? 'Super Administrator';

            // Create user
            $user = User::create([
                'name' => $name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'username' => $data->username ?? $this->generateUsername($name),
            ]);

            // Create profile
            $user->profile()->create([
                'full_name' => $name,
            ]);

            // Assign super_admin role
            $user->assignRole(Role::SUPER_ADMIN);

            // Set status to PROTECTED
            $user->setStatus(AccountStatus::PROTECTED);

            // Log audit entry
            $this->logAudit->execute(
                user: null,
                action: 'super_admin_created',
                subjectType: User::class,
                subjectId: $user->id,
                payload: [
                    'email' => $user->email,
                    'source' => 'cli',
                ],
                module: 'Setup',
            );

            return $user;
        });
    }

    private function generateUsername(string $name): string
    {
        $base = strtolower(str_replace(' ', '', $name));

        return substr($base, 0, 20);
    }
}
