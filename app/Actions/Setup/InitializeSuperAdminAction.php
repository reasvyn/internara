<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Core\LogAuditAction;
use App\Enums\Auth\AccountStatus;
use App\Enums\Auth\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class InitializeSuperAdminAction
{
    public function __construct(
        protected readonly LogAuditAction $logAudit,
    ) {}

    public function execute(string $email, string $password, ?string $name = null, ?string $username = null): User
    {
        return DB::transaction(function () use ($email, $password, $name, $username) {
            $name = $name ?? 'Super Administrator';

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'username' => $username ?? $this->generateUsername($name),
            ]);

            $user->profile()->create();

            $user->assignRole(Role::SUPER_ADMIN);
            $user->setStatus(AccountStatus::PROTECTED);

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
