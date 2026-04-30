<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * S1 - Secure: Implements secure registration logic.
 * S3 - Scalable: Stateless action.
 */
class RegisterAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    public function execute(array $data, ?array $roles = null): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'username' => $data['username'] ?? Str::uuid()->toString(), // Wait for UsernameGenerator in actual implementation
            'password' => Hash::make($data['password']),
        ]);

        if ($roles) {
            $user->assignRole($roles);
        }

        $this->logAuditAction->execute(
            action: 'user_registered',
            subjectType: User::class,
            subjectId: $user->id,
            payload: ['roles' => $roles],
            module: 'Auth'
        );

        return $user;
    }
}
