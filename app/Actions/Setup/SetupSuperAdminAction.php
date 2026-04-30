<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Setup the first Super Admin account.
 */
class SetupSuperAdminAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'setup_required' => false,
            ]);

            // Assign Super Admin role (assuming Spatie Permissions are set up)
            // $user->assignRole('super-admin'); 

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
