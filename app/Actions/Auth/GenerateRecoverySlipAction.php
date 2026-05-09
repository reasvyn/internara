<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Core\LogAuditAction;
use App\Models\AccountRecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class GenerateRecoverySlipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user): array
    {
        $plaintext = AccountRecoveryCode::generateCode();

        $code = AccountRecoveryCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($plaintext),
            'generated_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $this->logAudit->execute(
            action: 'recovery_slip_generated',
            subjectType: User::class,
            subjectId: $user->id,
            payload: ['expires_at' => $code->expires_at->toIso8601String()],
            module: 'Auth',
        );

        return [
            'code' => $code,
            'plaintext' => $plaintext,
        ];
    }
}
