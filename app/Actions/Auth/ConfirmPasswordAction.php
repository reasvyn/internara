<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Core\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ConfirmPasswordAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    public function execute(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            $this->logAuditAction->execute(
                action: 'password_confirmation_failed',
                subjectType: User::class,
                subjectId: $user->id,
                module: 'Auth',
            );

            throw new RuntimeException(__('auth.password_confirmation_failed') ?? 'The provided password does not match your current password.');
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->logAuditAction->execute(
            action: 'password_confirmed',
            subjectType: User::class,
            subjectId: $user->id,
            module: 'Auth',
        );
    }
}
