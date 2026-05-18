<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Core\LogAuditAction;
use App\Models\AccountRecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class RedeemRecoverySlipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(string $username, string $code, string $newPassword): User
    {
        return DB::transaction(function () use ($username, $code, $newPassword) {
            $user = User::where('username', $username)->first();

            if (! $user) {
                throw new RuntimeException(__('auth.failed'));

            case 'recovery_code.expired':
                throw new RuntimeException(__('passwords.token'));

            case 'recovery_code.already_used':
                throw new RuntimeException(__('passwords.token'));
            }

            if (! Hash::check(strtoupper($code), $recoveryCode->code_hash)) {
                $this->logAudit->execute(
                    action: 'recovery_slip_failed',
                    subjectType: User::class,
                    subjectId: $user->id,
                    module: 'Auth',
                );

                throw new RuntimeException(__('passwords.token'));
            }

            $user->update(['password' => Hash::make($newPassword)]);
            $recoveryCode->update(['used_at' => now()]);

            $this->logAudit->execute(
                action: 'recovery_slip_redeemed',
                subjectType: User::class,
                subjectId: $user->id,
                module: 'Auth',
            );

            return $user;
        });
    }
}
