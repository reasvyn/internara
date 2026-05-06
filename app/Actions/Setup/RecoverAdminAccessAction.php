<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Core\LogAuditAction;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Setup\Data\RecoverAdminData;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class RecoverAdminAccessAction
{
    public function __construct(
        private LogAuditAction $logAudit,
    ) {}

    public function execute(RecoverAdminData $data): User
    {
        return DB::transaction(function () use ($data) {
            if ($data->isReset) {
                $user = User::where('email', $data->email)->firstOrFail();
                $user->update([
                    'password' => Hash::make($data->password),
                    'locked_at' => null,
                    'locked_reason' => null,
                ]);
                $user->setStatus(AccountStatus::VERIFIED);
            } else {
                $user = User::create([
                    'name' => 'Recovery Admin',
                    'email' => $data->email,
                    'password' => Hash::make($data->password),
                    'username' => $this->generateUsername(),
                ]);
                $user->profile()->create([
                    'full_name' => 'Recovery Admin',
                ]);
                $user->setStatus(AccountStatus::PROTECTED);
            }

            $user->syncRoles([$data->role]);

            $this->logAudit->execute(
                user: null,
                action: 'admin_recovered',
                subjectType: User::class,
                subjectId: $user->id,
                payload: [
                    'type' => $data->isReset ? 'reset' : 'create',
                    'email' => $data->email,
                    'role' => $data->role,
                ],
                module: 'Setup',
            );

            return $user;
        });
    }

    private function generateUsername(): string
    {
        return 'admin_'.substr(md5(time()), 0, 8);
    }
}
