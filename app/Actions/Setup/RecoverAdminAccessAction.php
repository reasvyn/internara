<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Core\LogAuditAction;
use App\Enums\Auth\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class RecoverAdminAccessAction
{
    public function __construct(
        private LogAuditAction $logAudit,
    ) {}

    public function execute(string $email, string $password, bool $isReset = false, string $role = 'super_admin'): User
    {
        return DB::transaction(function () use ($email, $password, $isReset, $role) {
            if ($isReset) {
                $user = User::where('email', $email)->firstOrFail();
                $user->update([
                    'password' => Hash::make($password),
                    'locked_at' => null,
                    'locked_reason' => null,
                ]);
                $user->setStatus(AccountStatus::VERIFIED);
            } else {
                $user = User::create([
                    'name' => 'Recovery Admin',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'username' => $this->generateUsername(),
                ]);
                $user->profile()->create();
                $user->setStatus(AccountStatus::PROTECTED);
            }

            $user->syncRoles([$role]);

            $this->logAudit->execute(
                user: null,
                action: 'admin_recovered',
                subjectType: User::class,
                subjectId: $user->id,
                payload: [
                    'type' => $isReset ? 'reset' : 'create',
                    'email' => $email,
                    'role' => $role,
                ],
                module: 'Setup',
            );

            return $user;
        });
    }

    private function generateUsername(): string
    {
        return 'admin_'.substr(md5((string) time()), 0, 8);
    }
}
