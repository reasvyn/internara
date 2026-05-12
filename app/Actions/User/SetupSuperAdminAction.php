<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Actions\Core\LogAuditAction;
use App\Enums\Auth\Role as RoleEnum;
use App\Models\User;
use App\Support\User\HandlesActionErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Setup the first Super Admin account.
 *
 * S1 - Secure: Atomic super admin creation with validation.
 * S2 - Sustain: Proper error handling and logging.
 */
class SetupSuperAdminAction
{
    use HandlesActionErrors;

    public function __construct(protected readonly LogAuditAction $logAudit) {}

    /**
     * @param array{name: string, email: string, username: string, password: string} $data
     *
     * @throws RuntimeException when setup fails
     */
    public function execute(array $data): User
    {
        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();

        return $this->withErrorHandling(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $user = User::updateOrCreate(
                    ['username' => $data['username']],
                    [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($data['password']),
                        'setup_required' => false,
                    ],
                );

                $user->markEmailAsVerified();

                $user->assignRole(RoleEnum::SUPER_ADMIN->value);

                $this->logAudit->execute(
                    action: 'super_admin_created',
                    subjectType: User::class,
                    subjectId: $user->id,
                    payload: ['username' => $data['username']],
                    module: 'Setup',
                );

                return $user;
            });
        }, 'Failed to setup super admin');
    }
}
