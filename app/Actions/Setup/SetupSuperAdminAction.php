<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Rules\SystemUsername;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Setup the first Super Admin account.
 */
class SetupSuperAdminAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): User
    {
        Validator::make($data, [
            'username' => ['required', 'string', 'unique:users,username', new SystemUsername],
            'email' => ['required', 'email', 'unique:users,email'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'setup_required' => false,
            ]);

            // S1: Mark email as verified for the first super admin to allow immediate access
            $user->markEmailAsVerified();

            // Assign super_admin role using Spatie Permission (matches RoleEnum::SUPER_ADMIN)
            $user->assignRole('super_admin');

            $this->logAudit->execute(
                action: 'super_admin_created',
                subjectType: User::class,
                subjectId: $user->id,
                payload: ['username' => $data['username']],
                module: 'Setup'
            );

            return $user;
        });
    }
}
